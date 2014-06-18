<?php

namespace Oro\Bundle\IntegrationBundle\Model;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class IntegrationSettings implements \Serializable
{
    /** @var array */
    protected $settings;

    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
        $this->initialize();
    }

    /**
     * Set/Get setting
     *
     * @param   string $method
     * @param   array  $args
     *
     * @throws \RuntimeException
     * @return  mixed
     */
    public function __call($method, $args)
    {
        $prefix = substr($method, 0, 3);

        switch ($prefix) {
            case 'get':
                return $this->accessor->getValue($this->settings, $this->buildKey($method)) ? : null;
            case 'set':
                $value = isset($args[0]) ? $args[0] : null;
                $this->accessor->setValue($this->settings, $this->buildKey($method), $value);

                return null;
            case 'has':
                return isset($this->settings[$this->buildKey($method, false)]);
        }

        throw new \RuntimeException(sprintf('Call to undefined method %s of %s', $method, get_class($this)));
    }

    /**
     * Checks whether the object is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        if (empty($this->settings)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        unset($this->accessor);

        return serialize($this->settings);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->settings = unserialize($serialized);
        $this->initialize();
    }

    /**
     * Build a path for propertyAccess
     *
     * @param string $methodName
     *
     * @param bool   $pathWrap
     *
     * @return string
     */
    protected function buildKey($methodName, $pathWrap = true)
    {
        $key = lcfirst(substr($methodName, 3));

        if (!$pathWrap) {
            return $key;
        }

        return sprintf('[%s]', $key);
    }

    /**
     * Initializes property accessor
     */
    protected function initialize()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }
}
