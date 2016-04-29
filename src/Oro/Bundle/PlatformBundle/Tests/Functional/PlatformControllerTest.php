<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class PlatformControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            array(),
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
        $this->assertContains('Oro Packages', $content);
        $this->assertContains('3rd Party Packages', $content);
        $this->assertContains('symfony/symfony', $content);
    }
}
