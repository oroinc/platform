<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

/**
 * An instance of this class can be added to the metadata extras of the context
 * to request metadata for a particular action.
 */
class ActionMetadataExtra implements MetadataExtraInterface
{
    const NAME = 'action';

    /** @var string */
    private $action;

    /**
     * @param string $action The name of the action for which the metadata is requested
     */
    public function __construct($action)
    {
        $this->action = $action;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(MetadataContext $context)
    {
        $context->setTargetAction($this->action);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return self::NAME . ':' . $this->action;
    }
}
