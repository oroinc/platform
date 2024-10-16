<?php

namespace Oro\Bundle\SecurityBundle\Attribute;

use Attribute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * The CsrfProtection class handles the #[CsrfProtection} attribute
 *
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CsrfProtection implements ConfigurationInterface
{
    public const ALIAS_NAME = 'csrf_protection';

    public function __construct(
        private bool $enabled = true,
        private bool $useRequest = false,
    ) {
    }

    #[\Override]
    public function getAliasName()
    {
        return self::ALIAS_NAME;
    }

    #[\Override]
    public function allowArray()
    {
        return false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isUseRequest(): bool
    {
        return $this->useRequest;
    }

    public function setUseRequest(bool $useRequest): static
    {
        $this->useRequest = $useRequest;

        return $this;
    }
}
