<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SoapUsersACLTest extends WebTestCase
{

    const USER_NAME = 'user_wo_permissions';
    const USER_PASSWORD = 'user_api_key';

    const DEFAULT_USER_ID = '1';

    /** @var Client */
    protected $client;
    protected static $hasLoaded = false;

    public function setUp()
    {
        $this->client = self::createClient(array(), $this->generateWsseAuthHeader());
        $this->client->createSoapClient(
            "http://localhost/api/soap",
            array(
                'location' => 'http://localhost/api/soap',
                'soap_version' => SOAP_1_2
            )
        );

        if (!self::$hasLoaded) {
            $this->client->appendFixtures(__DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures');
        }
        self::$hasLoaded = true;
    }

    public function testWsseAccess()
    {
        try {
            $this->client->createSoapClient(
                "http://localhost/api/soap",
                array(
                    'location' => 'http://localhost/api/soap',
                    'soap_version' => SOAP_1_2
                )
            );
        } catch (\Exception $e) {
            $this->assertEquals('Forbidden', $e->getMessage());
        }
    }
}
