<?php
/**
 *
 * @package     OpenFlame Web Framework
 * @copyright   (c) 2010 OpenFlameCMS.com
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/OpenFlame/OpenFlame-Framework
 *
 * Minimum Requirement: PHP 5.3.0
 */

namespace OpenFlame\Framework\Asset;
use OpenFlame\Framework\Core;

if(!defined('OpenFlame\\ROOT_PATH')) exit;

/**
 * OpenFlame Web Framework - Asset instance object
 * 	     Represents the individual asset instance and its properties.
 *
 *
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 * @link        https://github.com/OpenFlame/OpenFlame-Framework
 */
class AssetInstance
{
	/**
	 * @var string - The name for this instance.
	 */
	protected $name = '';

	/**
	 * @var string - The asset type for this instance.
	 */
	protected $type = '';

	/**
	 * @var string - The relative URL for this instance.
	 */
	protected $url = '';

	/**
	 * @var string - The base URL used across all asset instances.
	 */
	protected $base_url = '';

	/**
	 * @var array - Array of various properties belonging to this asset instance.
	 */
	protected $properties = array();

	/**
	 * Get a new instance of this object.
	 * @return \OpenFlame\Framework\Asset\AssetInstance - The newly created instance.
	 */
	public static function newInstance()
	{
		return new static();
	}

	/**
	 * Get the asset name of this instance
	 * @return string - The asset name for this instance.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set the asset type for this instance
	 * @param string $name - The asset name to set.
	 * @return \OpenFlame\Framework\Asset\AssetInstance - Provides a fluent interface.
	 */
	public function setName($name)
	{
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Get the asset type of this instance
	 * @return string - The asset type for this instance.
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set the asset type for this instance
	 * @param string $type - The asset type to set.
	 * @return \OpenFlame\Framework\Asset\AssetInstance - Provides a fluent interface.
	 */
	public function setType($type)
	{
		$this->type = (string) $type;
		return $this;
	}

	/**
	 * Get the relative asset URL for this instance
	 * @return string - The relative asset URL for this instance.
	 */
	public function getURL()
	{
		return $this->url;
	}

	/**
	 * Set the relative asset URL for this instance
	 * @param string - The relative asset URL to set.
	 * @return \OpenFlame\Framework\Asset\AssetInstance - Provides a fluent interface.
	 */
	public function setURL($url)
	{
		$this->url = '/' . ltrim($url, '/');
		return $this;
	}

	/**
	 * Get a property of this asset instance.
	 * @param string $property - The name of the property to grab.
	 * @return mixed - NULL if no such property set, or the value of the property.
	 */
	public function getProperty($property)
	{
		if(isset($this->properties[(string) $property]))
		{
			return NULL;
		}

		return $this->properties[(string) $property];
	}

	/**
	 * Set an asset's property.
	 * @param string $property - The name of the property to set.
	 * @param mixed $value - The value to set for the property.
	 * @return \OpenFlame\Framework\Asset\AssetInstance - Provides a fluent interface.
	 */
	public function setProperty($property, $value)
	{
		$this->properties[(string) $property] = $value;

		return $this;
	}

	/**
	 * Get the "base URL" of this installation, which is added to the beginning of all asset URLs automatically.
	 * @return string - The base URL we are using.
	 */
	public function getBaseURL()
	{
		return $this->base_url;
	}

	/**
	 * Set the "base URL" for this installation, which will be added to the beginning of all asset URLs.
	 * @param string $base_url - The "base URL" which we're going to prepend.
	 * @return \OpenFlame\Framework\Asset\AssetInstance - Provides a fluent interface.
	 */
	public function setBaseURL($base_url)
	{
		$this->base_url = rtrim($base_url, '/'); // We don't want a trailing slash here.

		return $this;
	}

	/**
	 * Check to see if a specific property exists.
	 * @param string $name - The name of the property to check.
	 * @return bool - Has the property been set?
	 */
	public function __isset($name)
	{
		return isset($this->properties[(string) $name]);
	}

	/**
	 * Get a property of this asset instance.
	 * @param string $property - The name of the property to grab.
	 * @return mixed - NULL if no such property set, or the value of the property.
	 */
	public function __get($name)
	{
		if(isset($this->properties[(string) $property]))
		{
			return NULL;
		}

		return $this->properties[(string) $property];
	}

	/**
	 * Get the full URL for this specific asset
	 * @return string - The full (absolute) URL to the asset.
	 */
	public function __toString()
	{
		return $this->base_url . $this->url;
	}
}