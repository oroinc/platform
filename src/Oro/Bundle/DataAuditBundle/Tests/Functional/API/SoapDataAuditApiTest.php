<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SoapDataAuditApiTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client = null;

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
    }

    /**
     * @return array
     */
    public function testPreconditions()
    {
        //create users
        $request = array(
            "username" => 'user_' . mt_rand(),
            "email" => 'test_'  . mt_rand() . '@test.com',
            "enabled" => '1',
            "plainPassword" => '1231231q',
            "namePrefix" => "Mr",
            "firstName" => "firstName",
            "middleName" => "middleName",
            "lastName" => "lastName",
            "nameSuffix" => "Sn.",
            "roles" => array("2"),
            "owner" => "1"
        );

        $this->client->setServerParameters($this->generateWsseAuthHeader());
        $id = $this->client->getSoapClient()->createUser($request);
        $this->assertInternalType('int', $id, $this->client->getSoapClient()->__getLastResponse());
        $this->assertGreaterThan(0, $id);

        return $request;
    }

    /**
     * @param array $response
     * @return array
     * @depends testPreconditions
     */
    public function testGetAudits(array $response)
    {
        $result = $this->client->getSoapClient()->getAudits();
        $result = $this->valueToArray($result);

        if (!is_array(reset($result['item']))) {
            $result[] = $result['item'];
            unset($result['item']);
        } else {
            $result = $result['item'];
        }

        $resultActual = reset($result);

        $this->assertEquals($response['username'], $resultActual['objectName']);
        $this->assertEquals('admin', $resultActual['username']);

        return $result;
    }

    /**
     * @param array $response
     * @return array
     * @depends testGetAudits
     */
    public function testGetAudit($response)
    {
        foreach ($response as $audit) {
            $result = $this->client->getSoapClient()->getAudit($audit['id']);
            $result = $this->valueToArray($result);
            unset($result['loggedAt']);
            unset($audit['loggedAt']);
            $this->assertEquals($audit, $result);
        }
    }
}
