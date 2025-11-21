<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request human-readable descriptions of entities and fields.
 */
class DescriptionsConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'descriptions';

    private ?string $documentationAction;
    private ?string $resourceDocumentationAction;

    public function __construct(?string $documentationAction = null, ?string $resourceDocumentationAction = null)
    {
        $this->documentationAction = $documentationAction;
        $this->resourceDocumentationAction = $resourceDocumentationAction;
    }

    /**
     * Gets an action name for which fields' descriptions are requested.
     */
    public function getDocumentationAction(): ?string
    {
        return $this->documentationAction;
    }

    /**
     * Gets an action name for which the API resource documentation are requested.
     */
    public function getResourceDocumentationAction(): ?string
    {
        return $this->resourceDocumentationAction;
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureContext(ConfigContext $context): void
    {
        // no any modifications of the ConfigContext is required
    }

    #[\Override]
    public function isPropagable(): bool
    {
        return false;
    }

    #[\Override]
    public function getCacheKeyPart(): ?string
    {
        $result = self::NAME;
        if ($this->documentationAction) {
            $result .= ':' . $this->documentationAction;
        }

        return $result;
    }
}
