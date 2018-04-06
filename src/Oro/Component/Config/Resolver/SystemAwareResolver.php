<?php

namespace Oro\Component\Config\Resolver;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class SystemAwareResolver implements ResolverInterface, ContainerAwareInterface
{
    const PARENT_NODE = 'parent_node';
    const NODE_KEY    = 'node_key';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $context;

    /** @var PropertyAccessorInterface|null */
    protected $propertyAccessor;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $config, array $context = [])
    {
        $this->context = $context;
        $this->doResolve($config);
        $this->context = null;

        return $config;
    }

    /**
     * @param array $config
     */
    protected function doResolve(array &$config)
    {
        foreach ($config as $key => &$val) {
            if (is_array($val)) {
                $this->context[self::PARENT_NODE] = $val;

                $this->doResolve($val);
            } elseif (is_string($val)) {
                $this->context[self::NODE_KEY] = $key;

                $config[$key] = $this->resolveSystemCall($val);
            }
        }
    }

    /**
     * Replace static call, service call, constant or parameter access notation to value they returned
     *
     * @param string $val value to be resolved
     *
     * @return mixed
     */
    protected function resolveSystemCall($val)
    {
        if (!is_string($val)) {
            return $val;
        }

        if (strpos($val, '%') !== false) {
            $val = $this->resolveParameter($val);
        }

        if (is_string($val) && strpos($val, '::') !== false) {
            $val = $this->resolveStatic($val);
        }

        if (is_string($val) && strpos($val, '@') !== false) {
            $val = $this->resolveService($val);
        }

        return $val;
    }

    /**
     * @param string $oldVal
     * @param mixed  $newVal
     * @param string $match
     * @return mixed
     */
    protected function replaceValue($oldVal, $newVal, $match)
    {
        return (is_scalar($newVal) && $match !== $oldVal)
            ? str_replace($match, (string)$newVal, $oldVal)
            : $newVal;
    }

    /**
     * @param string $declaration
     * @return array
     */
    protected function getMethodCallParameters($declaration)
    {
        $result = [];

        $items = array_filter(explode(',', trim($declaration, '()')));
        foreach ($items as $item) {
            $item = trim($item, ' ');

            if ($this->startsWith($item, '$') && $this->endsWith($item, '$')) {
                $name = substr($item, 1, -1);
                $dot = strpos($name, '.');
                $objectName = $dot ? substr($name, 0, $dot) : $name;
                $item = $this->getContextValue($objectName);

                if ($dot) {
                    $propertyPath = substr($name, $dot + 1);
                    $item = $this->getPropertyAccessor()->getValue($item, $propertyPath);
                }
            } elseif ($this->startsWith($item, '%') && $this->endsWith($item, '%')) {
                $name = substr($item, 1, -1);
                $item = $this->getParameter($name);
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    protected function getContextValue($name)
    {
        return isset($this->context[$name]) ? $this->context[$name] : null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function getParameter($name)
    {
        return $this->container->getParameter($name);
    }

    /**
     * @param string $service
     * @return mixed
     */
    protected function getService($service)
    {
        return $this->container->get($service);
    }

    /**
     * @param string $service
     * @param string $method
     * @param array  $params
     * @return mixed
     */
    protected function callServiceMethod($service, $method, $params)
    {
        return call_user_func_array([$this->getService($service), $method], $params);
    }

    /**
     * @param string $class
     * @param string $method
     * @param array  $params
     * @return mixed
     */
    protected function callStaticMethod($class, $method, $params)
    {
        return call_user_func_array(
            sprintf('%s::%s', $class, $method),
            $params
        );
    }

    /**
     * Checks if a string starts with a given string.
     *
     * @param  string $haystack A string
     * @param  string $needle   A string to check against
     *
     * @return bool TRUE if $haystack starts with $needle
     */
    protected function startsWith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
    }

    /**
     * Checks if a string ends with a given string.
     *
     * @param  string $haystack A string
     * @param  string $needle   A string to check against
     *
     * @return bool TRUE if $haystack ends with $needle
     */
    protected function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Replace %parameter% with it's value.
     *
     * @param string $val
     * @return mixed
     */
    protected function resolveParameter($val)
    {
        if (preg_match('#%([\w\._]+)%#', $val, $match)) {
            $val = $this->replaceValue(
                $val,
                $this->container->getParameter($match[1]),
                $match[0]
            );
        }

        return $val;
    }

    /**
     * Resolve static call class:method or class::const
     *
     * @param string $val
     * @return mixed
     */
    protected function resolveStatic($val)
    {
        if (preg_match_all('#([^\(\'"%:\s]+)::([\w\._]+)(\([^\)]*\))?#', $val, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (!is_scalar($val)) {
                    break;
                }

                $class  = $match[1];
                $method = $match[2];

                $classMethod = [$class, $method];
                if (is_callable($classMethod)) {
                    $params = isset($match[3]) ? $this->getMethodCallParameters($match[3]) : [];
                    $val    = $this->replaceValue($val, $this->callStaticMethod($class, $method, $params), $match[0]);
                } elseif (defined(implode('::', $classMethod))) {
                    $val = $this->replaceValue(
                        $val,
                        constant(implode('::', $classMethod)),
                        $match[0]
                    );
                }
            }
        }

        return $val;
    }

    /**
     * Resolve service or service->method call.
     *
     * @param string $val
     * @return mixed
     */
    protected function resolveService($val)
    {
        if (strpos($val, '->') === false && preg_match('#@([\w\.]+)#', $val, $match)) {
            $val = $this->getService($match[1]);
        } elseif (preg_match('#@([\w\.]+)->([\w\.]+)(\([^\)]*\))?#', $val, $match)) {
            $params = isset($match[3]) ? $this->getMethodCallParameters($match[3]) : [];
            $val    = $this->replaceValue(
                $val,
                $this->callServiceMethod($match[1], $match[2], $params),
                $match[0]
            );
        }

        return $val;
    }

    /**
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
