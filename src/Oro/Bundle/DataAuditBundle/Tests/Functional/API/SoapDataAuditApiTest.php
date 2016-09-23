<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\API;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
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
     */
    public function testGetAudits()
    {
        $audit = new Audit();
        $audit->setObjectClass('object\class');
        $audit->setTransactionId('transaction-id');

        $em = $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($audit));
        $em->persist($audit);
        $em->flush();

        $result = $this->soapClient->getAudits();
        var_dump($result);
        $result = $this->valueToArray($result);

        if (!is_array(reset($result['item']))) {
            $result[] = $result['item'];
            unset($result['item']);
        } else {
            $result = $result['item'];
        }

        $resultActual = reset($result);

//        $this->assertEquals($response['username'], $resultActual['objectName']);
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

    private function getDoctrine()
    {
        return self::getContainer()->get('doctrine');
    }
}
