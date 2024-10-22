<?php

namespace Oro\Bundle\ImapBundle\Tests\Functional\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapClearManager;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadEmailUserData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadImapEmailData;
use Oro\Bundle\ImapBundle\Tests\Functional\DataFixtures\LoadUserEmailOriginData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageProcessTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ImapClearManagerTest extends WebTestCase
{
    use MessageProcessTrait;

    /** @var ImapClearManager */
    private $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
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

        $this->assertEquals(5, $this->getEntitiesCount(ImapEmail::class));

        $this->manager->clear(null);

        $this->assertEquals(2, $this->getEntitiesCount(UserEmailOrigin::class));
        $this->assertEquals(2, $this->getEntitiesCount(ImapEmailFolder::class));
        $this->assertEquals(3, $this->getEntitiesCount(ImapEmail::class));
        $this->assertEquals(8, $this->getEntitiesCount(EmailBody::class));

        $sentMessages = $this->getSentMessages();
        self::assertCount(1, $sentMessages);
        $message = $sentMessages[0];
        self::assertEquals('oro.search.index_entities', $message['topic']);
        self::assertEquals(EmailUser::class, $message['message']['class']);
        self::assertCount(4, $message['message']['entityIds']);
    }

    public function testClearById()
    {
        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3);
        $origin->setActive(false);

        $this->manager->clear($origin->getId());

        $this->assertEquals(2, $this->getEntitiesCount(UserEmailOrigin::class));
        $this->assertEquals(2, $this->getEntitiesCount(ImapEmailFolder::class));
        $this->assertEquals(3, $this->getEntitiesCount(ImapEmail::class));
        $this->assertEquals(8, $this->getEntitiesCount(EmailBody::class));

        $sentMessages = $this->getSentMessages();
        self::assertCount(1, $sentMessages);
        $message = $sentMessages[0];
        self::assertEquals('oro.search.index_entities', $message['topic']);
        self::assertEquals(EmailUser::class, $message['message']['class']);
        self::assertCount(4, $message['message']['entityIds']);
    }

    public function testClearByOfActiveOrigin()
    {
        /** @var UserEmailOrigin $origin */
        $origin = $this->getReference(LoadUserEmailOriginData::USER_EMAIL_ORIGIN_3);
        $origin->setActive(true);

        $this->manager->clear($origin->getId());

        $this->assertEquals(3, $this->getEntitiesCount(UserEmailOrigin::class));
        $this->assertEquals(3, $this->getEntitiesCount(ImapEmailFolder::class));
        $this->assertEquals(3, $this->getEntitiesCount(ImapEmail::class));
        $this->assertEquals(8, $this->getEntitiesCount(EmailBody::class));

        $sentMessages = $this->getSentMessages();
        self::assertCount(1, $sentMessages);
        $message = $sentMessages[0];
        self::assertEquals('oro.search.index_entities', $message['topic']);
        self::assertEquals(EmailUser::class, $message['message']['class']);
        self::assertCount(4, $message['message']['entityIds']);
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
