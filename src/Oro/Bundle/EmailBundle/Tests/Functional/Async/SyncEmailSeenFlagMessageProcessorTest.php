<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\SyncEmailSeenFlagMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;

/**
 * @dbIsolationPerTest
 */
class SyncEmailSeenFlagMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.processor.sync_email_seen_flag');

        $this->assertInstanceOf(SyncEmailSeenFlagMessageProcessor::class, $service);
    }

    public function testShouldSendMessageIfEmailUserSeenFieldChanged()
    {
        $emailUser = $this->createEmailUser();

        // guard
        $this->assertEmpty($this->getMessageProducer()->getTraces());

        // setSeen
        $emailUser->setSeen(true);
        $this->getEntityManager()->flush();

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);
        $this->assertEquals(Topics::SYNC_EMAIL_SEEN_FLAG, $traces[0]['topic']);
        $this->assertSame(['ids' => [$emailUser->getId()], 'seen' => true], $traces[0]['message']);

        // setUnseen
        $this->getMessageProducer()->clearTraces();
        $emailUser->setSeen(false);
        $this->getEntityManager()->flush();

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);
        $this->assertEquals(Topics::SYNC_EMAIL_SEEN_FLAG, $traces[0]['topic']);
        $this->assertSame(['ids' => [$emailUser->getId()], 'seen' => false], $traces[0]['message']);
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
