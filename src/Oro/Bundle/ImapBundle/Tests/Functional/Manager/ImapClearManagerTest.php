<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Manager;

use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapClearManager;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadEmailUserData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadImapEmailData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadUserEmailOriginData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ImapClearManagerTest extends WebTestCase
{
    /** @var ImapClearManager */
    private $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadImapEmailData::class,
            LoadEmailUserData::class,
        ]);

        $this->manager = $this->getContainer()->get('oro_imap.manager.clear');
    }

    public function testClear()
    {
        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3);
        $origin->setActive(false);

        $this->manager->clear(null);

        $this->assertEquals(2, $this->getEntitiesCount(UserEmailOrigin::class));
        $this->assertEquals(2, $this->getEntitiesCount(ImapEmailFolder::class));
        $this->assertEquals(3, $this->getEntitiesCount(ImapEmail::class));
    }

    public function testClearById()
    {
        /** @var UserEmailOrigin $origin1 */
        $origin1 = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_1);
        /** @var UserEmailOrigin $origin2 */
        $origin2 = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3);
        $origin2->setActive(false);

        $this->manager->clear($origin1->getId());

        $this->assertEquals(3, $this->getEntitiesCount(UserEmailOrigin::class));
        $this->assertEquals(3, $this->getEntitiesCount(ImapEmailFolder::class));
        $this->assertEquals(5, $this->getEntitiesCount(ImapEmail::class));
    }

    /**
     * @param string $class
     *
     * @return int
     */
    private function getEntitiesCount($class)
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository($class);

        return count($repository->findAll());
    }
}
