<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional;

use Oro\Bundle\AttachmentBundle\Tests\Functional\Stub\WebpConfigurationStub;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Stub\WebpFeatureVoterStub;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * Provides methods to substitute the WebP processing strategy in functional tests.
 * It is expected that this trait will be used in classes
 * derived from {@see \Oro\Bundle\TestFrameworkBundle\Test\WebTestCase}.
 */
trait WebpConfigurationTrait
{
    public static function getWebpStrategy(): string
    {
        $webpConfiguration = self::getWebpConfigurationStub();
        if ($webpConfiguration->isEnabledIfSupported()) {
            return WebpConfiguration::ENABLED_IF_SUPPORTED;
        }
        if ($webpConfiguration->isEnabledForAll()) {
            return WebpConfiguration::ENABLED_FOR_ALL;
        }

        return WebpConfiguration::DISABLED;
    }

    public static function setWebpStrategy(string $webpStrategy): void
    {
        if (self::getWebpStrategy() !== $webpStrategy) {
            self::getWebpFeatureVoterStub()->setWebpStrategy($webpStrategy);
            self::getWebpConfigurationStub()->setWebpStrategy($webpStrategy);
        }
    }

    /**
     * @beforeResetClient
     */
    public static function resetWebpStrategy(): void
    {
        self::getWebpFeatureVoterStub()->resetWebpStrategy();
        self::getWebpConfigurationStub()->resetWebpStrategy();
    }

    private static function getWebpFeatureVoterStub(): WebpFeatureVoterStub
    {
        $config = self::getContainer()->get('oro_attachment.checker.voter.webp_feature_voter');
        if (!$config instanceof WebpFeatureVoterStub) {
            throw new \LogicException(sprintf(
                'The service "oro_attachment.checker.voter.webp_feature_voter" should be instance of "%s", given "%s".',
                WebpFeatureVoterStub::class,
                get_class($config)
            ));
        }

        return $config;
    }

    private static function getWebpConfigurationStub(): WebpConfigurationStub
    {
        $config = self::getContainer()->get('oro_attachment.tools.webp_configuration');
        if (!$config instanceof WebpConfigurationStub) {
            throw new \LogicException(sprintf(
                'The service "oro_attachment.tools.webp_configuration" should be instance of "%s", given "%s".',
                WebpConfigurationStub::class,
                get_class($config)
            ));
        }

        return $config;
    }
}
