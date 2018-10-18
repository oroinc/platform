<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailControllerWithNonAdminEmailsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadEmailData::class]);
    }

    public function testCget()
    {
        $url = $this->getUrl('oro_api_get_emails');
        $this->client->request('GET', $url);

        $emails = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($emails);
        $this->assertCount(1, $emails);
    }
}
