<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Oro\Component\Layout\ContextItemInterface;

/**
 * \ArrayAccess is implemented to increase performance when this class is used with PropertyAccess component.
 */
class FormAction implements \ArrayAccess, ContextItemInterface
{
    const PATH = 'path';
    const ROUTE_NAME = 'route_name';
    const ROUTE_PARAMETERS = 'route_parameters';

    /** @var array */
    protected $data;

    /**
     * Creates the empty instance of FormAction.
     *
     * @return FormAction
     */
    public static function createEmpty()
    {
        return new self([]);
    }

    /**
     * Creates an instance of FormAction by the path.
     *
     * @param string $path
     *
     * @return FormAction
     *
     * @throws \InvalidArgumentException if the path is not a string or empty
     */
    public static function createByPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The path must be a string, but "%s" given.',
                    is_object($path) ? get_class($path) : gettype($path)
                )
            );
        }
        if (!$path) {
            throw new \InvalidArgumentException('The path must not be empty.');
        }

        return new self([self::PATH => $path]);
    }

    /**
     * Creates an instance of FormAction by the route.
     *
     * @param string $routeName
     * @param array  $routeParameters
     *
     * @return FormAction
     *
     * @throws \InvalidArgumentException if the route name is not a string or empty
     */
    public static function createByRoute($routeName, array $routeParameters = [])
    {
        if (!is_string($routeName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The route name must be a string, but "%s" given.',
                    is_object($routeName) ? get_class($routeName) : gettype($routeName)
                )
            );
        }
        if (!$routeName) {
            throw new \InvalidArgumentException('The route name must not be empty.');
        }

        return new self([self::ROUTE_NAME => $routeName, self::ROUTE_PARAMETERS => $routeParameters]);
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        if (isset($this->data[self::PATH])) {
            return 'path:' . $this->data[self::PATH];
        }
        if (isset($this->data[self::ROUTE_NAME])) {
            return 'route:' . $this->data[self::ROUTE_NAME];
        }

        return '';
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this[self::PATH];
    }

    /**
     * @return string|null
     */
    public function getRouteName()
    {
        return $this[self::ROUTE_NAME];
    }

    /**
     * @return array|null
     */
    public function getRouteParameters()
    {
        return $this[self::ROUTE_PARAMETERS];
    }

    /**
     * Indicates whether this object is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name)
    {
        return isset($this->data[$name])
            ? $this->data[$name]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as changing data is not allowed
     */
    public function offsetSet($name, $value)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as removing data is not allowed
     */
    public function offsetUnset($name)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * @param array $data
     */
    protected function __construct(array $data)
    {
        $this->data = $data;
    }
}
