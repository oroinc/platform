<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        $this->client = self::createClient(
            array(),
            $this->generateBasicHeader()
        );
    }

    public function testSystemInformation()
    {
        $this->client->request('GET', $this->client->generate('oro_platform_system_info'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $content = $result->getContent();
        $this->assertContains('Oro Packages', $content);
        $this->assertContains('3rd Party Packages', $content);
        $this->assertContains('symfony/symfony', $content);
    }
}
