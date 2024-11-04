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
    private ?string $parentAction;

    /**
     * @param string      $action       The name of the action for which the metadata is requested
     * @param string|null $parentAction The name of the action which causes the target action
     *                                  for which the metadata is requested
     */
    public function __construct(string $action, ?string $parentAction = null)
    {
        $this->action = $action;
        $this->parentAction = $parentAction;
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureContext(MetadataContext $context): void
    {
        $context->setTargetAction($this->action);
        if ($this->parentAction) {
            $context->setParentAction($this->parentAction);
        }
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        $result = self::NAME . ':' . $this->action;
        if ($this->parentAction) {
            $result .= ':' . $this->parentAction;
        }

        return $result;
    }
}
