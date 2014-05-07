<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;

/**
 * @outputBuffering enabled
 * @db_isolation
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

    public function testAbout()
    {
        $this->client->request('GET', $this->client->generate('oro_platform_about'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');

        $this->assertContains('Oro Packages', $result);
        $this->assertContains('3rd Party Packages', $result);
        $this->assertContains('symfony/symfony', $result);
    }
}
