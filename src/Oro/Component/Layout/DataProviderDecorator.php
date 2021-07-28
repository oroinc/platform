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
        $this->dataProvider = $dataProvider;
        $this->methodPrefixes = $methodPrefixes;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws \BadMethodCallException when calling a method with the given name is not allowed
     * @throws \Error when the decorated data provider does not have a method with the given name
     */
    public function __call($name, $arguments)
    {
        if (!preg_match(sprintf('/^(%s)(.+)$/i', implode('|', $this->methodPrefixes)), $name, $matches)) {
            throw new \BadMethodCallException(sprintf(
                'Method "%s" cannot be called. The method name should start with "%s".',
                $name,
                implode('", "', $this->methodPrefixes)
            ));
        }

        return $this->dataProvider->{$name}(...$arguments);
    }
}
