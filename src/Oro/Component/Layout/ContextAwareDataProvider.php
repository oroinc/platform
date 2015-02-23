<?php

namespace Oro\Component\Layout;

/**
 * The data provider that get data from the layout context.
 */
class ContextAwareDataProvider implements DataProviderInterface
{
    /** @var string */
    private $key;

    /** @var ContextInterface */
    private $context;

    /**
     * @param ContextInterface $context The layout context
     * @param string           $key     The context variable name
     */
    public function __construct(ContextInterface $context, $key)
    {
        $this->context = $context;
        $this->key     = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'context.' . $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->context[$this->key];
    }
}
