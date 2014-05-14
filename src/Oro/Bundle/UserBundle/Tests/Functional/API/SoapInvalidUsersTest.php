<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 */
class SoapInvalidUsersTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    public function testInvalidKey()
    {
        $this->client = self::createClient(
            array(),
            $this->generateWsseAuthHeader()
        );
        try {
            $this->client->createSoapClient(
                "http://localhost/api/soap",
                array(
                    'location' => 'http://localhost/api/soap',
                    'soap_version' => SOAP_1_2
                )
            );
        } catch (\Exception $e) {
            $this->assertEquals('Unauthorized', $e->getMessage());
        }
    }

    public function testInvalidUser()
    {
        $this->client = self::createClient(
            array(),
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        try {
            $this->client->createSoapClient(
                "http://localhost/api/soap",
                array(
                    'location' => 'http://localhost/api/soap',
                    'soap_version' => SOAP_1_2
                )
            );
        } catch (\Exception $e) {
            $this->assertEquals('Unauthorized', $e->getMessage());
        }
    }
}
