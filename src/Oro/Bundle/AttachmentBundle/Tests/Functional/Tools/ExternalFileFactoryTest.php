<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Tools;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\NullLogger;

/**
 * Verifies that redirect targets are re-validated against the configured allowed URLs regular expression,
 * using the real system configuration resolution (not a mocked ConfigManager).
 */
class ExternalFileFactoryTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const CONFIG_KEY = 'oro_attachment.external_file_allowed_urls_regexp';

    private ?string $originalRegExp = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->originalRegExp = (string)self::getConfigManager()->get(self::CONFIG_KEY);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setAllowedUrlsRegExp($this->originalRegExp);
    }

    public function testCreateFromUrlBlocksRedirectToDisallowedUrl(): void
    {
        $this->setAllowedUrlsRegExp('^http://allowed\.example\.test/');

        $factory = $this->getFactory([
            new Response(302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
        ]);

        $this->expectException(ExternalFileNotAccessibleException::class);
        $this->expectExceptionMessage(
            'Redirect to a URL that is not allowed by the external file URL configuration.'
        );

        $factory->createFromUrl('http://allowed.example.test/redirect');
    }

    public function testCreateFromUrlFollowsRedirectToAllowedUrl(): void
    {
        $this->setAllowedUrlsRegExp('^http://allowed\.example\.test/');

        $factory = $this->getFactory([
            new Response(302, ['Location' => 'http://allowed.example.test/final.png']),
            new Response(200, ['Content-Disposition' => 'inline;filename=image.png']),
        ]);

        self::assertEquals(
            new ExternalFile('http://allowed.example.test/redirect', 'image.png'),
            $factory->createFromUrl('http://allowed.example.test/redirect')
        );
    }

    private function setAllowedUrlsRegExp(string $value): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(self::CONFIG_KEY, $value);
        $configManager->flush();
    }

    private function getFactory(array $responses): ExternalFileFactory
    {
        $factory = new ExternalFileFactory(
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))]),
            [],
            new NullLogger()
        );
        $factory->setConfigManager(self::getConfigManager());

        return $factory;
    }
}
