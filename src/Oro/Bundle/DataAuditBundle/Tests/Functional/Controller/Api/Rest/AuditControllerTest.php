<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ResponseExtension;

/**
 * @dbIsolationPerTest
 */
class AuditControllerTest extends WebTestCase
{
    use ResponseExtension;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testShouldReturn401IfNotAuthenticated()
    {
        $this->client->setServerParameters([]);

        $this->client->request('GET', $this->getUrl('oro_api_get_audits'));

        $this->assertLastResponseStatus(401);
        $this->assertLastResponseContentTypeJson();
    }

    public function testShouldAllowGetAvailableAuditsAsArray()
    {
        $em = $this->getEntityManager();

        $user = $this->findAdmin();

        // guard
        $this->assertEquals('admin', $user->getUsername());

        $audit = new Audit();
        $audit->setAction('anAction');
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

        $this->client->request('GET', $this->getUrl('oro_api_get_audits'));

        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);

        $actualAudit = $result[0];
        $this->assertEquals($audit->getId(), $actualAudit['id']);
        $this->assertEquals('2012-12-12T00:00:00+00:00', $actualAudit['loggedAt']);
        $this->assertEquals(123, $actualAudit['objectId']);
        $this->assertEquals('aClass', $actualAudit['objectClass']);
        $this->assertEquals('aName', $actualAudit['objectName']);
        $this->assertEquals('admin', $actualAudit['username']);
        $this->assertEquals('anAction', $actualAudit['action']);
    }

    public function testShouldAllowGetAuditById()
    {
        $em = $this->getEntityManager();

        $user = $this->findAdmin();

        // guard
        $this->assertEquals('admin', $user->getUsername());

        $audit = new Audit();
        $audit->setAction('anAction');
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

        //guard
        $this->assertNotEmpty($audit->getId());

        $this->client->request('GET', $this->getUrl('oro_api_get_audit', ['id' => $audit->getId()]));
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $actualAudit = $result;
        $this->assertEquals($audit->getId(), $actualAudit['id']);
        $this->assertEquals('2012-12-12T00:00:00+00:00', $actualAudit['loggedAt']);
        $this->assertEquals(123, $actualAudit['objectId']);
        $this->assertEquals('aClass', $actualAudit['objectClass']);
        $this->assertEquals('aName', $actualAudit['objectName']);
        $this->assertEquals('admin', $actualAudit['username']);
        $this->assertEquals('anAction', $actualAudit['action']);
    }

    public function testShouldAllowGetInformationAboutChangedFields()
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
        $audit->addField(new AuditField('fooField', 'text', 'foo', null));
        $audit->addField(new AuditField('barField', 'text', 'bar2', 'bar1'));
        $em->persist($audit);

        $em->flush();

        //guard
        $this->assertNotEmpty($audit->getId());

        $this->client->request('GET', $this->getUrl('oro_api_get_audit', ['id' => $audit->getId()]));
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertEquals([
            'fooField' => ['old' => null, 'new' => 'foo'],
            'barField' => ['old' => 'bar1', 'new' => 'bar2'],
        ], $result['data']);
    }

    public function testShouldReturnAuditsWithLoggedAtGreatThenOrEqualDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt>='.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithLoggedAtGreatThenDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt>'.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithLoggedAtLessOrEqualThenDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt<='.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithLoggedAtLessThenDate()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-10 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-12 00:01+0000'));
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setLoggedAt(new \DateTime('2012-10-14 00:01+0000'));
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?loggedAt<'.urlencode('2012-10-12T00:01+0000')
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithActionEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setAction('create');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setAction('update');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setAction('remove');
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?action=create'
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithActionNotEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setAction('create');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setAction('update');
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setAction('remove');
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?action<>create'
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithObjectClassEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setObjectClass(TestAuditDataOwner::class);
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?objectClass='.TestAuditDataOwner::class
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnAuditsWithObjectClassNotEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setObjectClass(TestAuditDataChild::class);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setObjectClass(TestAuditDataOwner::class);
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?objectClass<>'.TestAuditDataOwner::class
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(2, $result);
    }

    public function testShouldReturnAuditsWithUserEqualsToGivenOne()
    {
        $em = $this->getEntityManager();

        $user = $this->findAdmin();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setUser($user);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setUser(null);
        $em->persist($audit);

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setUser(null);
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?user='.$user->getId()
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(1, $result);
    }

    public function testShouldReturnNothingIfGivenUserDoesNotExist()
    {
        $em = $this->getEntityManager();

        $user = $this->findAdmin();

        $audit = new Audit();
        $audit->setObjectName('aName');
        $audit->setObjectClass('aClass');
        $audit->setObjectId(123);
        $audit->setTransactionId('aTransactionId');
        $audit->setUser($user);
        $em->persist($audit);

        $em->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_audits').'?user=0'
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeJson();

        $result = $this->getLastResponseJsonContent();

        $this->assertInternalType('array', $result);

        $this->assertCount(0, $result);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->client->getContainer()->get('doctrine.orm.entity_manager');
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
