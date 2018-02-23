<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImapBundle\Entity\Repository\UserEmailOriginRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadEmailUserData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadImapEmailData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadUserEmailOriginData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class UserEmailOriginRepositoryTest extends WebTestCase
{
    /** @var DoctrineHelper */
    protected $doctrineHeler;

    protected function setUp()
    {
        $this->initClient();
        $this->doctrineHeler = $this->getContainer()->get('oro_entity.doctrine_helper');
    }

    public function testDeleteRelatedEmails()
    {
        $this->loadFixtures([LoadEmailUserData::class]);

        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1);

        $this->assertEquals(10, $this->getEntitiesCount(Email::class));

        /** @var UserEmailOriginRepository $repo */
        $repo = $this->doctrineHeler->getEntityRepositoryForClass(UserEmailOrigin::class);
        $repo->deleteRelatedEmails($origin);

        $this->assertEquals(7, $this->getEntitiesCount(Email::class));
    }

    public function testDeleteRelatedEmailsSyncDisabled()
    {
        $this->loadFixtures([LoadImapEmailData::class]);

        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3);

        $this->assertEquals(10, $this->getEntitiesCount(Email::class));

        /** @var UserEmailOriginRepository $repo */
        $repo = $this->doctrineHeler->getEntityRepositoryForClass(UserEmailOrigin::class);
        $repo->deleteRelatedEmails($origin, false);

        $this->assertEquals(8, $this->getEntitiesCount(Email::class));
    }

    public function testDeleteRelatedEmailsSyncEnabled()
    {
        $this->loadFixtures([LoadImapEmailData::class]);

        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1);

        $this->assertEquals(10, $this->getEntitiesCount(Email::class));

        /** @var UserEmailOriginRepository $repo */
        $repo = $this->doctrineHeler->getEntityRepositoryForClass(UserEmailOrigin::class);
        $repo->deleteRelatedEmails($origin, true);

        $this->assertEquals(7, $this->getEntitiesCount(Email::class));
    }

    /**
     * @param string $class
     *
     * @return int
     */
    private function getEntitiesCount($class)
    {
        /** @var ObjectRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository($class);

        return count($repository->findAll());
    }
}
