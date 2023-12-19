<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PermissionsPolicyHeaderRequestListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->followRedirects();
    }

    public function testPermissionsPolicyHeaderPresence()
    {
        $this->client->request('GET', $this->getUrl('oro_user_security_login'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertResponseHasHeader('Permissions-Policy');
    }
}
