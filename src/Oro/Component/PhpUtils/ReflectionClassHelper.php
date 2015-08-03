<?php

namespace Oro\Component\PhpUtils;

class ReflectionClassHelper
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
     * Returns the last occurred error
     *
     * @return string
     */
    public function getLastError()
    {
        $error           = $this->lastError;
        $this->lastError = null;

        return $error;
    }

    /**
     * Checks whether the given method exist in class declaration
     *
     * @param string $method The name of a method
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
     * Validates arguments list. $this->getLastError() will return error message if this method return FALSE
     *
     * @param string $method    The name of a method
     * @param array  $arguments The list of expected arguments
     *
     * @return bool
     */
    public function isValidArguments($method, array $arguments)
    {
        $refMethod = $this->getMethod($method);

        $params     = $refMethod->getParameters();
        $paramNames = $this->mapParameterListToNames($params);

        $argumentsCount = count($arguments);
        $argumentsKeys  = array_keys($arguments);
        $isAssoc        = ArrayUtil::isAssoc($arguments);
        $diff           = array_diff($argumentsKeys, $paramNames);

        if ($isAssoc && $diff) {
            $this->lastError = sprintf('Unknown argument(s) for "%s" method given: ', $method);
            $this->lastError .= implode(', ', $diff);

            return false;
        } elseif (!$isAssoc && $refMethod->getNumberOfParameters() < $argumentsCount) {
            $this->lastError = sprintf('Number of arguments given greater than declared in "%s" method', $method);

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
                $this->lastError = sprintf('Missing required argument(s) for "%s" method: ', $method);
                $this->lastError .= implode(', ', $missingArguments);

                return false;
            }
        } elseif ($argumentsCount < $refMethod->getNumberOfRequiredParameters()) {
            $this->lastError = sprintf(
                '"%s" method requires at least %d argument(s) to be passed, %d given',
                $method,
                $refMethod->getNumberOfRequiredParameters(),
                $argumentsCount
            );

            return false;
        }

        return true;
    }

    /**
     * Completes arguments array by default values that were not passed, but set at declaration
     *
     * @param string $method    The name of a method
     * @param array  $arguments The list of arguments
     */
    public function completeArguments($method, array &$arguments)
    {
        if (ArrayUtil::isAssoc($arguments)) {
            $result    = [];
            $refMethod = $this->getMethod($method);
            $params    = $refMethod->getParameters();

            foreach ($params as $param) {
                $hasValue = isset($arguments[$param->name]) || array_key_exists($param->name, $arguments);

                if (!$hasValue && $param->isOptional() && !empty($arguments)) {
                    $result[$param->name] = $param->getDefaultValue();
                } elseif ($hasValue) {
                    $result[$param->name] = $arguments[$param->name];
                }

                unset($arguments[$param->name]);
            }

            $arguments = $result;
        }
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return string[]
     */
    protected function mapParameterListToNames(array $parameters)
    {
        return array_map(
            function (\ReflectionParameter $parameter) {
                return $parameter->name;
            },
            $parameters
        );
    }

    /**
     * Lazy initialization of reflection class instance
     *
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
     * Lazy initialization of reflection method instance
     *
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
