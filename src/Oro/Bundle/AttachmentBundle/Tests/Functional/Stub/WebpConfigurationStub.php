<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Stub;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * The decorator for WebpConfiguration that allows to substitute
 * the WebP processing strategy in functional tests.
 */
class WebpConfigurationStub extends WebpConfiguration
{
    private WebpConfiguration $webpConfiguration;
    private ?string $stubWebpStrategy = null;

    public function __construct(WebpConfiguration $webpConfiguration)
    {
        $this->webpConfiguration = $webpConfiguration;
    }

    public function setWebpStrategy(string $webpStrategy): void
    {
        $this->stubWebpStrategy = $webpStrategy;
    }

    public function resetWebpStrategy(): void
    {
        $this->stubWebpStrategy = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebpQuality(): int
    {
        return $this->webpConfiguration->getWebpQuality();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabledIfSupported(): bool
    {
        if (null !== $this->stubWebpStrategy) {
            return self::ENABLED_IF_SUPPORTED === $this->stubWebpStrategy;
        }

        return $this->webpConfiguration->isEnabledIfSupported();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabledForAll(): bool
    {
        if (null !== $this->stubWebpStrategy) {
            return self::ENABLED_FOR_ALL === $this->stubWebpStrategy;
        }

        return $this->webpConfiguration->isEnabledForAll();
    }

    /**
     * {@inheritDoc}
     */
    public function isDisabled(): bool
    {
        if (null !== $this->stubWebpStrategy) {
            return self::DISABLED === $this->stubWebpStrategy;
        }

        return $this->webpConfiguration->isDisabled();
    }
}
