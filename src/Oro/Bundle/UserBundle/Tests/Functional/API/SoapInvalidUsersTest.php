<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 */
class SoapInvalidUsersTest extends WebTestCase
{
    public function testInvalidKey()
    {
        $this->markTestIncomplete("ACL is not working for SOAP");
        $this->initClient(
            array(),
            $this->generateWsseAuthHeader()
        );
        $this->initSoapClient();
    }

    public function testInvalidUser()
    {
        $this->markTestIncomplete("ACL is not working for SOAP");
        $this->initClient(
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $this->initSoapClient();
    }
}
