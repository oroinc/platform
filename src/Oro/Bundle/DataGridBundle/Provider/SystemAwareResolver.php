<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\PhpUtils\ArrayUtil;

class SystemAwareResolver implements ContainerAwareInterface
{
    const KEY_EXTENDS       = 'extends';
    const KEY_EXTENDED_FROM = 'extended_from';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array parent configuration array node
     */
    protected $parentNode;

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * @param string $datagridName
     * @param array  $datagridDefinition
     * @param bool   $recursion
     *
     * @return array
     */
    public function resolve($datagridName, $datagridDefinition, $recursion = false)
    {
        foreach ($datagridDefinition as $key => $val) {
            if (is_array($val)) {
                $this->parentNode         = $val;
                $datagridDefinition[$key] = $this->resolve($datagridName, $val, true);
                continue;
            }

            $val = $this->resolveSystemCall($datagridName, $key, $val);
            if (!$recursion && self::KEY_EXTENDS === $key) {
                // get parent grid definition, resolved
                $definition = $this->container
                    ->get('oro_datagrid.datagrid.manager')
                    ->getConfigurationForGrid($val);

                // merge them and remove extend directive
                $datagridDefinition = ArrayUtil::arrayMergeRecursiveDistinct(
                    $definition->toArray(),
                    $datagridDefinition
                );
                unset($datagridDefinition['extends']);

                $datagridDefinition[self::KEY_EXTENDED_FROM]   = isset($datagridDefinition[self::KEY_EXTENDED_FROM]) ?
                    $datagridDefinition[self::KEY_EXTENDED_FROM] : [];
                $datagridDefinition[self::KEY_EXTENDED_FROM][] = $val;

                // run resolve again on merged grid definition
                $datagridDefinition = $this->resolve($val, $datagridDefinition);

                // break current loop cause we've just extended grid definition
                break;
            }

            $datagridDefinition[$key] = $val;
        }

        return $datagridDefinition;
    }

    /**
     * Replace static call, service call or constant access notation to value they returned
     * while building datagrid
     *
     * @param string $datagridName
     * @param string $key key from datagrid definition (columns, filters, sorters, etc)
     * @param string $val value to be resolved/replaced
     *
     * @return mixed
     */
    protected function resolveSystemCall($datagridName, $key, $val)
    {
        // resolve only scalar value, if it's not - value was already resolved
        // this can happen in case of extended grid definitions
        if (!is_scalar($val)) {
            return $val;
        }

        while (is_scalar($val) && strpos($val, '::') !== false) {
            $newVal = $this->resolveStatic($datagridName, $key, $val);
            if ($newVal == $val) {
                break;
            }
            $val = $newVal;
        }

        if (is_scalar($val) && strpos($val, '@') !== false) {
            $val = $this->resolveService($datagridName, $key, $val);
        }

        return $val;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Resolve static call class:method or class::const
     *
     * @param string $datagridName
     * @param string $key
     * @param string $val
     * @return mixed
     */
    protected function resolveStatic($datagridName, $key, $val)
    {
        if (preg_match('#([^\'"%:\s]+)::([\w\._]+)#', $val, $match)) {
            $matchedString = $match[0];
            $class = $match[1];
            $method = $match[2];

            $classMethod = [$class, $method];
            if (is_callable($classMethod)) {
                return $this->replaceValueInString(
                    $val,
                    $matchedString,
                    call_user_func_array($classMethod, [$datagridName, $key, $this->parentNode])
                );
            } elseif (defined(implode('::', $classMethod))) {
                return $this->replaceValueInString(
                    $val,
                    $matchedString,
                    constant(implode('::', $classMethod))
                );
            }
        }

        return $val;
    }

    /**
     * Resolve service or service->method call.
     *
     * @param string $datagridName
     * @param string $key
     * @param string $val
     * @return mixed
     */
    protected function resolveService($datagridName, $key, $val)
    {
        if (strpos($val, '\@') !== false) {
            return str_replace('\@', '@', $val);
        }

        $serviceRegex = '@(?P<lazy>\??)(?P<service>[\w\.]+)';
        $methodRegex = '(?P<method>\w+)';
        $argumentsRegex = '(?P<arguments>\(.*?\))';

        $service = null;
        $method = null;
        $arguments = null;
        $matchedString = null;
        $lazy = false;
        if (strpos($val, '->') !== false) {
            if (preg_match("~{$serviceRegex}->{$methodRegex}{$argumentsRegex}~six", $val, $matches)) {
                // Match @service->method("argument")
                $matchedString = $matches[0];
                $lazy = (bool) $matches['lazy'];
                $service = $matches['service'];
                $method = $matches['method'];
                $arguments = $this->getArguments($matches['arguments']);
            } elseif (preg_match("~{$serviceRegex}->{$methodRegex}~six", $val, $matches)) {
                // Match @service->method
                $matchedString = $matches[0];
                $lazy = (bool) $matches['lazy'];
                $service = $matches['service'];
                $method = $matches['method'];
                $arguments = [
                    $datagridName,
                    $key,
                    $this->parentNode
                ];
            }
        } else {
            if (preg_match("~{$serviceRegex}~six", $val, $matches)) {
                // Match @service
                $service = $matches['service'];
            }
        }

        if ($service) {
            // Resolve service
            $service = $this->container->get($service);

            // Perform method call
            if ($method) {
                if ($lazy) {
                    return function () use ($val, $matchedString, $service, $method, $arguments) {
                        return $this->replaceValueInString(
                            $val,
                            $matchedString,
                            call_user_func_array([$service, $method], $arguments)
                        );
                    };
                }

                return $this->replaceValueInString(
                    $val,
                    $matchedString,
                    call_user_func_array([$service, $method], $arguments)
                );
            }

            return $service;
        }

        return $val;
    }

    /**
     * Replace matched string with resolved value in original string.
     *
     * Example:
     *    Input: Hello, @user_provider->getCurrentUserName
     *    Output: Hello, Some User
     *
     * @param string $originalString
     * @param string $matchedString
     * @param mixed $resolved
     * @return mixed
     */
    protected function replaceValueInString($originalString, $matchedString, $resolved)
    {
        if (is_scalar($resolved) && $originalString !== $matchedString) {
            return str_replace($matchedString, (string)$resolved, $originalString);
        }

        return $resolved;
    }

    /**
     * Get arguments as array from parsed arguments string.
     *
     * Example:
     *      Input: ("The", 'answer', 42)
     *      Output: ['The', 'answer', 42]
     *
     * @param string $argumentsString
     * @return array
     */
    protected function getArguments($argumentsString)
    {
        $argumentsString = trim($argumentsString);
        $argumentsString = trim($argumentsString, '()');
        $arguments = explode(',', $argumentsString);

        return array_map(
            function ($val) {
                $val = trim($val);
                return trim($val, '\'"');
            },
            $arguments
        );
    }
}
