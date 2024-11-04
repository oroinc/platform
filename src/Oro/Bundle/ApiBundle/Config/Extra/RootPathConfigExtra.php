<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to specify a root path to an association for which the entity configuration is built.
 */
class RootPathConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'path';

    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Gets the path to an association.
     */
    public function getPath(): string
    {
        return $this->path;
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
        return 'path:' . $this->path;
    }
}
