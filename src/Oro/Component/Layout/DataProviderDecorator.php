<?php

namespace Oro\Component\Layout;

/**
 * The data provider decorator that allows calls methods with pre-defined prefix
 */
class DataProviderDecorator
{
    /**
     * @var object
     */
    protected $dataProvider;

    /**
     * @var string[]
     */
    protected $methodPrefixes;

    /**
     * @param object $dataProvider
     * @param string[] $methodPrefixes
     */
    public function __construct($dataProvider, $methodPrefixes)
    {
        if (!is_object($dataProvider)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Data provider must be "object" instance, "%s" given.',
                    gettype($dataProvider)
                )
            );
        }

        $this->dataProvider = $dataProvider;
        $this->methodPrefixes = $methodPrefixes;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if (preg_match(sprintf('/^(%s)(.+)$/i', implode('|', $this->methodPrefixes)), $name, $matches)) {
            if (!method_exists($this->dataProvider, $name)) {
                throw new \BadMethodCallException(
                    sprintf(
                        'Method "%s" not found in "%s".',
                        $name,
                        get_class($this->dataProvider)
                    )
                );
            }
            return call_user_func_array([$this->dataProvider, $name], $arguments);
        } else {
            throw new \BadMethodCallException(
                sprintf(
                    'Method "%s" cannot be called. The called method should begin with "%s".',
                    $name,
                    implode('", "', $this->methodPrefixes)
                )
            );
        }
    }
}
