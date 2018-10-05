<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\SyncEmailSeenFlagMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Bridge\Monolog\Logger;

/**
 * @dbIsolationPerTest
 */
class SyncEmailSeenFlagMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.sync_email_seen_flag');

        $this->assertInstanceOf(SyncEmailSeenFlagMessageProcessor::class, $service);
    }

    public function testShouldSendMessageIfEmailUserSeenFieldChanged()
    {
        $emailUser = $this->createEmailUser();

        // setSeen
        $emailUser->setSeen(true);
        $this->getEntityManager()->flush();

        $this->assertMessageSent(Topics::SYNC_EMAIL_SEEN_FLAG, ['id' => $emailUser->getId(), 'seen' => true]);

        // setUnseen
        $emailUser->setSeen(false);
        $this->getEntityManager()->flush();

        $this->assertMessageSent(Topics::SYNC_EMAIL_SEEN_FLAG, ['id' => $emailUser->getId(), 'seen' => false]);
    }

    public function testProcessWithEmptyFoldersCollection()
    {
        $emailUser = $this->createEmailUser();
        $emailUser->setSeen(true);
        $this->getEntityManager()->flush();

        /** @var SyncEmailSeenFlagMessageProcessor $processor */
        $processor = $this->getContainer()->get('oro_email.async.sync_email_seen_flag');
        $messageData = $this->getSentMessage(Topics::SYNC_EMAIL_SEEN_FLAG);

        $message = new NullMessage();
        $message->setBody(json_encode($messageData));
        
        $this->assertEquals(SyncEmailSeenFlagMessageProcessor::ACK, $processor->process($message, new NullSession()));
    }

    public function testProcessForInternalEmail()
    {
        $internalOrigin = new InternalEmailOrigin();
        $internalOrigin->setActive(true);
        $this->getEntityManager()->persist($internalOrigin);

        $folder = new EmailFolder();
        $folder->setName('Local');
        $folder->setFullName('Local');
        $folder->setType(FolderType::OTHER);
        $folder->setOrigin($internalOrigin);
        $this->getEntityManager()->persist($folder);

        $emailUser = $this->createEmailUser();
        $emailUser->addFolder($folder);
        $emailUser->setSeen(true);
        $this->getEntityManager()->flush();

        /** @var SyncEmailSeenFlagMessageProcessor $processor */
        $processor = $this->getContainer()->get('oro_email.async.sync_email_seen_flag');
        $messageData = $this->getSentMessage(Topics::SYNC_EMAIL_SEEN_FLAG);

        $message = new NullMessage();
        $message->setBody(json_encode($messageData));

        /** @var Logger $logger */
        $logger = $this->getContainer()->get('logger');
        $logger->pushHandler(new DebugHandler());
        $this->getContainer()->set('logger', $logger);

        $this->assertEquals(SyncEmailSeenFlagMessageProcessor::ACK, $processor->process($message, new NullSession()));
        $this->assertEmpty($logger->getLogs());
    }

    /**
     * @return EmailUser
     */
    private function createEmailUser()
    {
        $emailOrigin = new InternalEmailOrigin();
        $emailOrigin->setActive(true);

        $emailAddress = new EmailAddressProxy();

        $email = new Email();
        $email->setSubject('subject');
        $email->setFromName('from');
        $email->setSentAt(new \DateTime());
        $email->setInternalDate(new \DateTime());
        $email->setMessageId('12345');
        $email->setFromEmailAddress($emailAddress);

        $emailAddress->setEmail($email);

        $emailUser = new EmailUser();
        $emailUser->setEmail($email);
        $emailUser->setReceivedAt(new \DateTime());
        $emailUser->setOrigin($emailOrigin);
        $emailUser->setSeen(false);

        $this->getEntityManager()->persist($email);
        $this->getEntityManager()->persist($emailAddress);
        $this->getEntityManager()->persist($emailOrigin);
        $this->getEntityManager()->persist($emailUser);
        $this->getEntityManager()->flush();

        return $emailUser;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(EmailUser::class);
    }
}
