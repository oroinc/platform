<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator\Utils;

class ReflectionUtils
{
    /** @var string */
    protected $className;

    /** @var \ReflectionClass */
    protected $refClass;

    /** @var \ReflectionMethod[] */
    protected $refMethods = [];

    /** @var string */
    protected $lastError;

    /**
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getLastError()
    {
        $error           = $this->lastError;
        $this->lastError = null;

        return $error;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function hasMethod($method)
    {
        if (empty($method)) {
            return false;
        }

        return $this->getRefClass()->hasMethod($method);
    }

    /**
     * @param string $action
     * @param array  $arguments
     *
     * @return bool
     */
    public function isValidArguments($action, array $arguments)
    {
        $method = $this->getMethod($action);

        $params     = $method->getParameters();
        $paramNames = $this->mapParameterListToNames($params);

        $argumentsCount = count($arguments);
        $argumentsKeys  = array_keys($arguments);
        $isAssoc        = !(count(array_filter($argumentsKeys, 'is_numeric')) === $argumentsCount);
        $diff           = array_diff($argumentsKeys, $paramNames);

        if ($isAssoc && $diff) {
            $this->lastError = sprintf('Unknown argument(s) for "%s" action given: ', $action);
            $this->lastError .= implode(', ', $diff);

            return false;
        } elseif (!$isAssoc && $method->getNumberOfParameters() < $argumentsCount) {
            $this->lastError = sprintf('Number of arguments given greater than declared in "%s" action', $action);

            return false;
        }

        if ($isAssoc || $argumentsCount === 0) {
            $requiredParams     = array_filter(
                $params,
                function (\ReflectionParameter $param) {
                    return !$param->isOptional();
                }
            );
            $requiredParamNames = $this->mapParameterListToNames($requiredParams);

            $missingArguments = array_diff($requiredParamNames, $argumentsKeys);
            if (!empty($missingArguments)) {
                $this->lastError = sprintf('Missing required argument(s) for "%s" action: ', $action);
                $this->lastError .= implode(', ', $missingArguments);

                return false;
            }
        } elseif ($argumentsCount < $method->getNumberOfRequiredParameters()) {
            $this->lastError = sprintf(
                '"%s" action requires at least %d argument(s) to be passed, %d given',
                $action,
                $method->getNumberOfRequiredParameters(),
                $argumentsCount
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $method
     * @param array  $arguments
     */
    public function completeArguments($method, array &$arguments)
    {
        $argumentsKeys = array_keys($arguments);
        $isAssoc       = !(count(array_filter($argumentsKeys, 'is_numeric')) === count($arguments));

        if ($isAssoc) {
            $result = [];
            $method = $this->getMethod($method);
            $params = $method->getParameters();

            foreach ($params as $param) {
                $hasValue = array_key_exists($param->getName(), $arguments);

                if ($param->isOptional() && !empty($arguments) && !$hasValue) {
                    $result[$param->getName()] = $param->getDefaultValue();
                } elseif ($hasValue) {
                    $result[$param->getName()] = $arguments[$param->getName()];
                }

                unset($arguments[$param->getName()]);
            }

            $arguments = $result;
        }
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return array
     */
    protected function mapParameterListToNames(array $parameters)
    {
        return array_map(
            function (\ReflectionParameter $parameter) {
                return $parameter->getName();
            },
            $parameters
        );
    }

    /**
     * @return \ReflectionClass
     */
    protected function getRefClass()
    {
        if (null === $this->refClass) {
            $this->refClass = new \ReflectionClass($this->className);
        }

        return $this->refClass;
    }

    /**
     * @param string $method
     *
     * @return \ReflectionMethod
     */
    protected function getMethod($method)
    {
        if (!isset($this->refMethods[$method])) {
            $this->refMethods[$method] = $this->getRefClass()->getMethod($method);
        }

        return $this->refMethods[$method];
    }
}
