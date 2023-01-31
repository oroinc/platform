<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PlatformControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader()
        );
        $this->client->useHashNavigation(true);
    }

    public function testSystemInformation()
    {
        $this->client->request('GET', $this->getUrl('oro_platform_system_info'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $content = $result->getContent();
        self::assertStringContainsString('Environment', $content);
        self::assertStringContainsString('Oro Packages', $content);
        self::assertStringContainsString('3rd Party Packages', $content);
        self::assertStringContainsString('doctrine/dbal', $content);
    }
}
