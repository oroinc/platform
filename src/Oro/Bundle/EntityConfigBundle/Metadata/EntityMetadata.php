<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata;

/**
 * Represents an entity metadata for configurable entities.
 */
final class EntityMetadata
{
    public string $name;
    public ?string $mode = null;
    public ?array $defaultValues = null;
    public ?string $routeName = null;
    public ?string $routeView = null;
    public ?string $routeCreate = null;
    /** @var string[] [route type => route, ...] */
    public array $routes = [];
    /** @var FieldMetadata[] */
    public $fieldMetadata = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function merge(EntityMetadata $object): void
    {
        $this->name = $object->name;
        $this->mode = $object->mode;
        $this->defaultValues = $object->defaultValues;
        $this->routeName = $object->routeName;
        $this->routeView = $object->routeView;
        $this->routeCreate = $object->routeCreate;
        $this->routes = $object->routes;
        $this->fieldMetadata = array_merge($this->fieldMetadata, $object->fieldMetadata);
    }

    public function addFieldMetadata(FieldMetadata $metadata): void
    {
        $this->fieldMetadata[$metadata->name] = $metadata;
    }

    public function __serialize(): array
    {
        return [
            $this->name,
            $this->mode,
            $this->defaultValues,
            $this->routeName,
            $this->routeView,
            $this->routeCreate,
            $this->routes,
            $this->fieldMetadata
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->name,
            $this->mode,
            $this->defaultValues,
            $this->routeName,
            $this->routeView,
            $this->routeCreate,
            $this->routes,
            $this->fieldMetadata
        ] = $serialized;
    }

    /**
     * @return string[] [route type => route, ...]
     */
    public function getRoutes(): array
    {
        return array_filter(array_merge(
            $this->routes,
            [
                'name'   => $this->routeName,
                'view'   => $this->routeView,
                'create' => $this->routeCreate
            ]
        ));
    }

    /**
     * @param string $routeType The type of a route
     * @param bool   $strict    Whether an exception should be thrown if no route of given route type does not exist
     *                          or a default route name should be generated
     *
     * @return string
     */
    public function getRoute(string $routeType = 'view', bool $strict = false): string
    {
        $propertyName = 'route' . ucfirst($routeType);
        if (property_exists($this, $propertyName)) {
            if ($this->{$propertyName}) {
                return $this->{$propertyName};
            }
            if (!$strict) {
                return $this->generateDefaultRoute($routeType);
            }
        } elseif (\array_key_exists($routeType, $this->routes)) {
            return $this->routes[$routeType];
        }

        throw new \LogicException(sprintf('No route "%s" found for entity "%s"', $routeType, $this->name));
    }

    /**
     * @param string $routeType The type of a route
     * @param bool   $strict    Whether this method should return TRUE only when the given route type exists
     *                          or when the given route type exists or a default route name can be generated
     *
     * @return bool
     */
    public function hasRoute(string $routeType = 'view', bool $strict = false): bool
    {
        $propertyName = 'route' . ucfirst($routeType);

        return
            (property_exists($this, $propertyName) && !$strict)
            || (property_exists($this, $propertyName) && $strict && $this->{$propertyName})
            || \array_key_exists($routeType, $this->routes);
    }

    private function generateDefaultRoute(string $routeType): string
    {
        static $routeMap = [
            'view'   => 'view',
            'name'   => 'index',
            'create' => 'create'
        ];
        $postfix = $routeMap[$routeType];
        $parts = explode('\\', $this->name);

        return strtolower(reset($parts)) . '_' . strtolower(end($parts)) . '_' . $postfix;
    }
}
