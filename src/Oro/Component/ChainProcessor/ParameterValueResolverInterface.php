<?php

namespace Oro\Component\ChainProcessor;

/**
 * Represents a class responsible for resolving a parameter value in ParameterBag.
 * @see \Oro\Component\ChainProcessor\ParameterBagInterface::setResolver
 */
interface ParameterValueResolverInterface
{
    /**
     * Whether the resolver can resolve the given value.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function supports($value);

    /**
     * Gets the resolved value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function resolve($value);
}
