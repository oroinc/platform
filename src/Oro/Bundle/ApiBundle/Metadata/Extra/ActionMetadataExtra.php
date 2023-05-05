<?php

namespace Oro\Bundle\ApiBundle\Metadata\Extra;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;

/**
 * An instance of this class can be added to the metadata extras of the context
 * to request metadata for a particular action.
 */
class ActionMetadataExtra implements MetadataExtraInterface
{
    public const NAME = 'action';

    private string $action;

    /**
     * @param string $action The name of the action for which the metadata is requested
     */
    public function __construct(string $action)
    {
        $this->action = $action;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(MetadataContext $context): void
    {
        $context->setTargetAction($this->action);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return self::NAME . ':' . $this->action;
    }
}
