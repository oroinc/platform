<?php

namespace Oro\Component\Config\Resolver;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SystemAwareResolver implements ResolverInterface, ContainerAwareInterface
{
    const PARENT_NODE = 'parent_node';
    const NODE_KEY    = 'node_key';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $context;

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
    public function resolve(array $config, array $context = array())
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
        switch (true) {
            // static call class:method or class::const
            case preg_match('#%([\w\.]+)%::([\w\._]+)#', $val, $match):
                // with class as param
                $class = $this->getParameter($match[1]);
                // fall-through
            case preg_match('#([^\'"%:\s]+)::([\w\.]+)(\([^\)]*\))?#', $val, $match):
                // with class real name
                if (!isset($class)) {
                    $class = $match[1];
                }
                $method = $match[2];
                if (is_callable([$class, $method])) {
                    $params = isset($match[3]) ? $this->getMethodCallParameters($match[3]) : array();
                    $val    = $this->replaceValue($val, $this->callStaticMethod($class, $method, $params), $match[0]);
                } elseif (!isset($match[3]) && defined("$class::$method")) {
                    $val = $this->replaceValue($val, constant("$class::$method"), $match[0]);
                }
                break;
            // service method call @service->method
            case preg_match('#@([\w\.]+)->([\w\.]+)(\([^\)]*\))?#', $val, $match):
                $params = isset($match[3]) ? $this->getMethodCallParameters($match[3]) : array();
                $val    = $this->replaceValue($val, $this->callServiceMethod($match[1], $match[2], $params), $match[0]);
                break;
            // parameter %parameter name%
            case preg_match('#%([\w\.]+)%#', $val, $match):
                $val = $this->replaceValue($val, $this->getParameter($match[1]), $match[0]);
                break;
            // service pass @service
            case preg_match('#@([\w\.]+)#', $val, $match):
                $val = $this->getService($match[1]);
                break;
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
        $result = array();

        $items = explode(',', trim($declaration, '()'));
        foreach ($items as $item) {
            $item = trim($item, ' ');

            if ($this->startsWith($item, '$') && $this->endsWith($item, '$')) {
                $name = substr($item, 1, -1);
                $item = (isset($this->context[$name]) || array_key_exists($name, $this->context))
                    ? $this->context[$name]
                    : null;
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
        return call_user_func_array(
            array($this->getService($service), $method),
            $params
        );
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
        return substr($haystack, -strlen($needle)) == $needle;
    }
}
