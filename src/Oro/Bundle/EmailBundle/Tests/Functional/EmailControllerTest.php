<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
    }

    public function testView()
    {
        $url = $this->getUrl('oro_email_view', ['id' => $this->getReference('email_1')->getId()]);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

// activity
// create
// body
// attachment
// emails
// baseEmails
// userEmails
}
