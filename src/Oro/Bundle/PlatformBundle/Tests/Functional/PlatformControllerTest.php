<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class PlatformControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(
            array(),
            ToolsAPI::generateBasicHeader()
        );
    }

    public function testSystemInformation()
    {
        $this->client->request('GET', $this->client->generate('oro_platform_system_info'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');

        $content = $result->getContent();
        $this->assertContains('Oro Packages', $content);
        $this->assertContains('3rd Party Packages', $content);
        $this->assertContains('symfony/symfony', $content);
    }
}
