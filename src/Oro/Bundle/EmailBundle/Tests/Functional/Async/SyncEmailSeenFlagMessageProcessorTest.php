<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\SyncEmailSeenFlagMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;

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

        $this->assertMessageSent(Topics::SYNC_EMAIL_SEEN_FLAG, ['ids' => [$emailUser->getId()], 'seen' => true]);

        // setUnseen
        $emailUser->setSeen(false);
        $this->getEntityManager()->flush();

        $this->assertMessageSent(Topics::SYNC_EMAIL_SEEN_FLAG, ['ids' => [$emailUser->getId()], 'seen' => false]);
    }

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
     * @return \Oro\Component\MessageQueue\Client\MessageProducer|TraceableMessageProducer
     */
    private function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(EmailUser::class);
    }
}
