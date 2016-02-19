<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata;

use Metadata\MergeableClassMetadata;
use Metadata\MergeableInterface;

class EntityMetadata extends MergeableClassMetadata
{
    /**
     * @var bool
     */
    public $configurable = false;

    /**
     * @var string
     */
    public $routeName;

    /**
     * @var string
     */
    public $routeView;

    /**
     * @var string
     */
    public $routeCreate;

    /**
     * @var string
     */
    public $mode;

    /**
     * @var array
     */
    public $defaultValues;

    /**
     * @var array
     */
    public $routes = [];

    /**
     * {@inheritdoc}
     */
    public function merge(MergeableInterface $object)
    {
        parent::merge($object);

        if ($object instanceof EntityMetadata) {
            $this->configurable  = $object->configurable;
            $this->defaultValues = $object->defaultValues;
            $this->routeName     = $object->routeName;
            $this->routeView     = $object->routeView;
            $this->routeCreate   = $object->routeCreate;
            $this->routes        = $object->routes;
            $this->mode          = $object->mode;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->configurable,
                $this->defaultValues,
                $this->routeName,
                $this->routeView,
                $this->routeCreate,
                $this->routes,
                $this->mode,
                parent::serialize(),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list(
            $this->configurable,
            $this->defaultValues,
            $this->routeName,
            $this->routeView,
            $this->routeCreate,
            $this->routes,
            $this->mode,
            $parentStr
            ) = unserialize($str);

        parent::unserialize($parentStr);
    }

    /**
     * @param string $routeType Route Type
     * @param bool   $strict    Should exception be thrown if no route of given type found
     *
     * @return string
     */
    public function getRoute($routeType = 'view', $strict = false)
    {
        if (in_array($routeType, ['view', 'name', 'create'], true)) {
            $propertyName = 'route' . ucfirst($routeType);

            if ($this->{$propertyName}) {
                return $this->{$propertyName};
            } elseif (false === $strict) {
                return $this->generateDefaultRoute($routeType);
            }
        } elseif (!$strict && array_key_exists($routeType, $this->routes)) {
            return $this->routes[$routeType];
        }

        throw new \LogicException(sprintf('No route "%s" found for entity "%s"', $routeType, $this->name));
    }

    /**
     * @param string $routeType
     *
     * @return string
     */
    protected function generateDefaultRoute($routeType)
    {
        static $routeMap = [
            'view'   => 'view',
            'name'   => 'index',
            'create' => 'create'
        ];
        $postfix = $routeMap[$routeType];
        $parts   = explode('\\', $this->name);

        return strtolower(reset($parts)) . '_' . strtolower(end($parts)) . '_' . $postfix;
    }
}
