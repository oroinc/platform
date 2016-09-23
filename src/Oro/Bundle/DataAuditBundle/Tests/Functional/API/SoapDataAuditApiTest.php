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

    public function testShouldReturnListOfAudits()
    {
        $audit = new Audit();
        $audit->setObjectId(12345);
        $audit->setObjectClass('object\class');
        $audit->setObjectName('object-name');
        $audit->setVersion(567);
        $audit->setTransactionId('transaction-id');

        $em = $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($audit));
        $em->persist($audit);
        $em->flush();

        $result = $this->soapClient->getAudits();
        $result = $this->valueToArray($result);

        if (!is_array(reset($result['item']))) {
            $result[] = $result['item'];
            unset($result['item']);
        } else {
            $result = $result['item'];
        }

        $resultActual = reset($result);

        $this->assertEquals($audit->getId(), $resultActual['id']);
        $this->assertEquals(12345, $resultActual['objectId']);
        $this->assertEquals('object\class', $resultActual['objectClass']);
        $this->assertEquals('object-name', $resultActual['objectName']);
        $this->assertEquals(567, $resultActual['version']);
    }

    public function testShouldReturnOne()
    {
        $audit = new Audit();
        $audit->setObjectId(12345);
        $audit->setObjectClass('object\class');
        $audit->setObjectName('object-name');
        $audit->setVersion(5678);
        $audit->setTransactionId('transaction-id');

        $em = $this->getDoctrine()->getManagerForClass(ClassUtils::getClass($audit));
        $em->persist($audit);
        $em->flush();

        $result = $this->soapClient->getAudit($audit->getId());
        $result = $this->valueToArray($result);

        $this->assertEquals($audit->getId(), $result['id']);
        $this->assertEquals(12345, $result['objectId']);
        $this->assertEquals('object\class', $result['objectClass']);
        $this->assertEquals('object-name', $result['objectName']);
        $this->assertEquals(5678, $result['version']);
    }

    private function getDoctrine()
    {
        return self::getContainer()->get('doctrine');
    }
}
