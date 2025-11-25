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

    private ?string $documentationAction = null;
    private ?string $resourceDocumentationAction = null;

    /**
     * Gets an action name for which fields' descriptions are requested.
     */
    public function getDocumentationAction(): ?string
    {
        return $this->documentationAction;
    }

    /**
     * Sets an action name for which fields' descriptions are requested.
     */
    public function setDocumentationAction(?string $documentationAction): void
    {
        $this->documentationAction = $documentationAction;
    }

    /**
     * Gets an action name for which the API resource documentation are requested.
     */
    public function getResourceDocumentationAction(): ?string
    {
        return $this->resourceDocumentationAction;
    }

    /**
     * Sets an action name for which the API resource documentation are requested.
     */
    public function setResourceDocumentationAction(?string $resourceDocumentationAction): void
    {
        $this->resourceDocumentationAction = $resourceDocumentationAction;
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
    public function configureContext(ConfigContext $context): void
    {
        // no any modifications of the ConfigContext is required
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        $result = self::NAME;
        if ($this->documentationAction) {
            $result .= ':' . $this->documentationAction;
        }

        return $result;
    }
}
