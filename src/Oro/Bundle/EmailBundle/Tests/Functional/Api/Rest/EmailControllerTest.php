<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
    }

    /**
     * @return array
     */
    public function testCget()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_emails'
            )
        );

        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    public function testGet()
    {
        $id = $this->getReference('email_1')->getId();
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_email', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals('My Web Store Introduction', $result['subject']);
        $this->assertContains('Thank you for signing up to My Web Store!', $result['emailBody']['content']);

        return $result['id'];
    }
}
