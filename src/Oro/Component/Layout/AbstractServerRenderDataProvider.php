<?php

namespace Oro\Component\Layout;

/**
 * If you use only server side rendering of layouts you don't need to implement getIdentifier method,
 * just raise BadMethodCallException exception.
 */
abstract class AbstractServerRenderDataProvider implements DataProviderInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;
    
    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        $this->context = $context;

        return $this;
    }
}
