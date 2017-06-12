<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @group soap
 */
class SoapDataAuditApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
    }

    /**
     * @return array
     */
    public function testPreconditions()
    {
        // create users
        $request = [
            'username'      => 'user_' . mt_rand(),
            'email'         => 'test_' . mt_rand() . '@test.com',
            'enabled'       => '1',
            'plainPassword' => '1231231q',
            'namePrefix'    => 'Mr',
            'firstName'     => 'firstName',
            'middleName'    => 'middleName',
            'lastName'      => 'lastName',
            'nameSuffix'    => 'Sn.',
            'roles'         => ['2'],
            'owner'         => '1'
        ];

        $this->client->setServerParameters($this->generateWsseAuthHeader());
        $id = $this->soapClient->createUser($request);
        $this->assertInternalType('int', $id, $this->soapClient->__getLastResponse());
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
        $result = $this->soapClient->getAudits();
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
            $result = $this->soapClient->getAudit($audit['id']);
            $result = $this->valueToArray($result);
            unset($result['loggedAt']);
            unset($audit['loggedAt']);
            $this->assertEquals($audit, $result);
        }
    }
}
