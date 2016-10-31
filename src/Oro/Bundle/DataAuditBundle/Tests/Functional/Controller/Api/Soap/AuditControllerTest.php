<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Controller\Api\Soap;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @outputBuffering enabled
 * @group soap
 */
class AuditControllerTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->initSoapClient();
        $this->startTransaction();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
        self::$loadedFixtures = [];
    }

    public function testShouldAllowGetAvailableAuditsAsArray()
    {
        $em = $this->getEntityManager();

        $user = $this->findAdmin();

        // guard
        $this->assertEquals('admin', $user->getUsername());

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('anTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-12-12 00:00:00+0000'));
        $audit->setUser($user);
        $audit->setVersion(2);
        $em->persist($audit);

        $anotherAudit = new Audit();
        $anotherAudit->setObjectName('aName');
        $anotherAudit->setObjectClass('aClass');
        $anotherAudit->setObjectId(345);
        $anotherAudit->setTransactionId('anTransactionId');
        $em->persist($anotherAudit);

        $em->flush();

        $result = $this->soapClient->getAudits();
        $result = $this->valueToArray($result);

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('item', $result);
        $this->assertCount(2, $result['item']);

        $actualAudit = $result['item'][0];
        $this->assertEquals($audit->getId(), $actualAudit['id']);
        $this->assertEquals('2012-12-12T00:00:00+00:00', $actualAudit['loggedAt']);
        $this->assertEquals(123, $actualAudit['objectId']);
        $this->assertEquals('aClass', $actualAudit['objectClass']);
        $this->assertEquals('aName', $actualAudit['objectName']);
        $this->assertEquals('admin', $actualAudit['username']);
    }

    public function testShouldAllowGetAuditById()
    {
        $em = $this->getEntityManager();

        $user = $this->findAdmin();

        // guard
        $this->assertEquals('admin', $user->getUsername());

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('anTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-12-12 00:00:00+0000'));
        $audit->setUser($user);
        $audit->setVersion(2);
        $em->persist($audit);

        $em->flush();

        //guard
        $this->assertNotEmpty($audit->getId());

        $result = $this->soapClient->getAudit($audit->getId());
        $result = $this->valueToArray($result);

        $this->assertInternalType('array', $result);

        $actualAudit = $result;
        $this->assertEquals($audit->getId(), $actualAudit['id']);
        $this->assertEquals('2012-12-12T00:00:00+00:00', $actualAudit['loggedAt']);
        $this->assertEquals(123, $actualAudit['objectId']);
        $this->assertEquals('aClass', $actualAudit['objectClass']);
        $this->assertEquals('aName', $actualAudit['objectName']);
        $this->assertEquals('admin', $actualAudit['username']);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getClient()->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return User
     */
    private function findAdmin()
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'username' => 'admin'
        ]);
    }
}
