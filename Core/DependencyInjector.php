<?php
/**
 *
 * @package     openflame-framework
 * @subpackage  dependency
 * @copyright   (c) 2010 - 2011 openflame-project.org
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/OpenFlame/OpenFlame-Framework
 *
 * Minimum Requirement: PHP 5.3.0
 */

namespace OpenFlame\Framework\Core;
use OpenFlame\Framework\Core\Core;
use OpenFlame\Framework\Core\Internal\LogicException;

/**
 * OpenFlame Framework - Dependency injector
 * 	     Provides fluid dependency injection.
 *
 *
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/OpenFlame/OpenFlame-Framework
 */
class DependencyInjector implements \ArrayAccess
{
	/**
	 * @var array - Array of closures which prepare the requested instance on demand.
	 */
	protected $injectors = array();

	/**
	 * @var \OpenFlame\Framework\Core\DependencyInjector - Singleton instance of the dependency injector
	 */
	protected static $instance;

	/**
	 * Constructor
	 */
	protected function __construct()
	{
		// Avoiding problems with use() here, need to pass $injector and not $this to the closure
		$injector = $this;

		// Define a bunch of injectors
		$this->setInjector('router', '\\OpenFlame\\Framework\\Router\\Router');
		$this->setInjector('alias_router', '\\OpenFlame\\Framework\\Router\\AliasRouter');
		$this->setInjector('input', '\\OpenFlame\\Framework\\Input\\Handler');
		$this->setInjector('template', '\\OpenFlame\\Framework\\Twig\\Variables');
		$this->setInjector('asset', '\\OpenFlame\\Framework\\Twig\\Helper\\Asset\\Manager');
		$this->setInjector('form', '\\OpenFlame\\Framework\\Core\\Utility\\FormKey');
		$this->setInjector('dispatcher', '\\OpenFlame\\Framework\\Event\\Dispatcher');
		$this->setInjector('language', '\\OpenFlame\\Framework\\Language\\Handler');
		$this->setInjector('url', '\\OpenFlame\\Framework\\Twig\\Helper\\URL\\Builder');
		$this->setInjector('cookie', '\\OpenFlame\\Framework\\Cookie\\Manager');
		$this->setInjector('seeder', '\\OpenFlame\\Framework\\Security\\Seeder');
		$this->setInjector('timer', '\\OpenFlame\\Framework\\Twig\\Helper\\Timer\\Timer');
		$this->setInjector('session_store_engine', '\\OpenFlame\\Framework\\Session\\Storage\\EngineFilesystem');
		$this->setInjector('session_client_engine', '\\OpenFlame\\Framework\\Session\\Client\\EngineCookie');
		$this->setInjector('header', '\\OpenFlame\\Framework\\Header\\Manager');

		$this->setInjector('asset_proxy', function() use($injector) {
			return new \OpenFlame\Framework\Twig\Helper\Asset\Proxy($injector->get('asset'));
		});

		$this->setInjector('url_proxy', function() use($injector) {
			return new \OpenFlame\Framework\Twig\Helper\URL\BuilderProxy($injector->get('url'));
		});

		$this->setInjector('language_proxy', function() use($injector) {
			return new \OpenFlame\Framework\Language\Proxy($injector->get('language'));
		});

		$this->setInjector('session', function() use($injector) {
			$session = new \OpenFlame\Framework\Session\Driver();
			$session->setStorageEngine($injector->get('session_store_engine'));
			$session->setClientEngine($injector->get('session_client_engine'));

			return $session;
		});

		$this->setInjector('cache.engine', function() use($injector) {
			return $injector::grab('cache.engine.' . (Core::getConfig('cache.engine') ?: 'serialize'));
		});

		$this->setInjector('cache.engine.json', function() use($injector) {
			$engine = new \OpenFlame\Framework\Cache\Engine\File\FileEngineJSON();
			$engine->setCachePath(Core::getConfig('cache.path'));
			return $engine;
		});

		$this->setInjector('cache.engine.serialize', function() use($injector) {
			$engine = new \OpenFlame\Framework\Cache\Engine\File\FileEngineSerialize();
			$engine->setCachePath(Core::getConfig('cache.path'));
			return $engine;
		});

		$this->setInjector('cache.engine.apc', '\\OpenFlame\\Framework\\Cache\\Engine\\APCEngine');

		$this->setInjector('cache', function() use($injector) {
			$cache = new \OpenFlame\Framework\Cache\Driver();
			$cache->setEngine($injector->get('cache.engine'));
			return $cache;
		});

		$this->setInjector('twig', function() {
			$twig = new \OpenFlame\Framework\Twig\Wrapper();
			$twig->setTwigRootPath(Core::getConfig('twig.lib_path'))
				->setTwigCachePath(Core::getConfig('twig.cache_path'))
				->setTemplatePath(Core::getConfig('twig.template_path'))
				->setTwigOption('debug', (Core::getConfig('twig.debug') ?: false));
			$twig->initTwig();

			return $twig;
		});
	}

	/**
	 * Get the singleton instance of the dependency injector.
	 * @return \OpenFlame\Framework\Core\DependencyInjector - Singleton instance of the dependency injector
	 */
	public static function getInstance()
	{
		if(self::$instance === NULL)
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Allows quickly grabbing a dependency without using getInstance to fetch the currently stored static instance of the dependency injector (hybrid of self::getInstance() and self->get())
	 * @param string $name - The name of the dependency to inject.
	 * @return object - The object we are injecting.
	 */
	public static function grab($name)
	{
		$self = self::getInstance();
		return $self->get($name);
	}

	/**
	 * Get a dependency (and fire the injector if the dependency has not been instantiated)
	 * @param string $name - The name of the dependency to inject.
	 * @return object - The object we are injecting.
	 */
	public function get($name)
	{
		$object = Core::getObject($name);
		if($object === NULL)
		{
			$object = $this->fireInjector($name);
		}

		if($object instanceof \Closure)
		{
			return $object();
		}

		return $object;
	}

	/**
	 * Register a new dependency injector closure.
	 * @param string $name - The name of the dependency
	 * @param mixed $injector - Either the closure or class to instantiate when injecting the dependency
	 * @return \OpenFlame\Framework\Core\DependencyInjector - Provides a fluent interface.
	 */
	public function setInjector($name, $injector)
	{
		if(!($injector instanceof \Closure))
		{
			$injector = (string) $injector;
		}

		$this->injectors[$name] = $injector;

		return $this;
	}

	/**
	 * Removes the specified injector
	 * @param string $name - The name of the dependency
	 * @return \OpenFlame\Framework\Core\DependencyInjector - Provides a fluent interface.
	 */
	public function unsetInjector($name)
	{
		$this->injectors[$name] = NULL;

		return $this;
	}

	/**
	 * Check to see if an injector has been defined for a particular dependency.
	 * @param string $name - The name of the dependency to check.
	 * @return boolean - Is the injector present?
	 */
	public function injectorPresent($name)
	{
		return !empty($this->injectors[$name]);
	}

	/**
	 * Get the injector closure.
	 * @param string $name - The name of the component to grab the injector for.
	 * @return mixed - Returns the dependency injector to use.
	 *
	 * @throws LogicException
	 */
	protected function getInjector($name)
	{
		if(!isset($this->injectors[$name]))
		{
			throw new LogicException(sprintf('Cannot fetch dependency object "%s", no injector defined', $name));
		}
		return $this->injectors[$name];
	}

	/**
	 * Trigger the dependency injector and store a reference to the resulting object in the OpenFlame Framework core
	 * @param string $name - The name of the dependency to inject.
	 * @return object - The object that we are injecting.
	 */
	protected function fireInjector($name)
	{
		$injector = $this->getInjector($name);

		if($injector instanceof \Closure)
		{
			return Core::setObject($name, $injector());
		}
		else
		{
			return Core::setObject($name, new $injector());
		}
	}

	/**
	 * ArrayAccess methods
	 */

	/**
	 * Check if an "array" offset exists in this object.
	 * @param mixed $offset - The offset to check.
	 * @return boolean - Does anything exist for this offset?
	 */
	public function offsetExists($offset)
	{
		return $this->injectorPresent($offset);
	}

	/**
	 * Get an "array" offset for this object.
	 * @param mixed $offset - The offset to grab from.
	 * @return mixed - The value of the offset, or null if the offset does not exist.
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * Set an "array" offset to a certain value, if the offset exists
	 * @param mixed $offset - The offset to set.
	 * @param mixed $value - The value to set to the offset.
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->setInjector($offset, $value);
	}

	/**
	 * Unset an "array" offset.
	 * @param mixed $offset - The offset to clear out.
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		$this->unsetInjector($offset);
	}
}
