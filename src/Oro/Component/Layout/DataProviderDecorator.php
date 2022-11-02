<?php

namespace Oro\Component\Layout;

/**
 * The data provider decorator that allows to call methods that have name starts with "get", "has", "is".
 */
class DataProviderDecorator
{
    private const PATTERN = '/^(get|has|is)(.+)$/i';

    /** @var object */
    private $dataProvider;

    public function __construct(object $dataProvider)
    {
        $this->dataProvider = $dataProvider;
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
        if (!preg_match(self::PATTERN, $name, $matches)) {
            throw new \BadMethodCallException(sprintf(
                'Method "%s" cannot be called. The method name should start with "get", "has" or "is".',
                $name
            ));
        }

        return $this->dataProvider->{$name}(...$arguments);
    }
}
