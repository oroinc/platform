<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use ReflectionClass;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Command\AutoResponseCommand;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\EventListener\AutoResponseListener;

class AutoResponseListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $autoResponseRuleRepository;
    protected $em;
    protected $uow;

    protected $autoResponseListener;

    public function setUp()
    {
        $this->autoResponseRuleRepository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroEmailBundle:AutoResponseRule')
            ->will($this->returnValue($this->autoResponseRuleRepository));

        $this->autoResponseListener = new AutoResponseListener();
    }

    /**
     * @dataProvider testProvider
     */
    public function testListenerShouldNotFlushJobIfRulesDoesntExists(array $entityInsertions, array $expectedArgs)
    {
        $this->autoResponseRuleRepository
            ->expects($this->once())
            ->method('rulesExists')
            ->will($this->returnValue(false));

        $this->uow->expects($this->never())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($entityInsertions));

        $this->autoResponseListener->onFlush(new OnFlushEventArgs($this->em));
        $this->autoResponseListener->postFlush(new PostFlushEventArgs($this->em));
    }

    /**
     * @dataProvider testProvider
     */
    public function testListenerShouldFlushJobIfRulesExists(array $entityInsertions, array $expectedArgs)
    {
        $this->autoResponseRuleRepository
            ->expects($this->once())
            ->method('rulesExists')
            ->will($this->returnValue(true));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($entityInsertions));

        $this->em->expects($this->once())
            ->method('persist')
            ->with(new Job(AutoResponseCommand::NAME, $expectedArgs));
        $this->em->expects($this->once())
            ->method('flush');

        $this->autoResponseListener->onFlush(new OnFlushEventArgs($this->em));
        $this->autoResponseListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testProvider()
    {
        return [
            [
                [
                    $this->createEmailBody(1),
                ],
                ['--id=1'],
            ],
            [
                [
                    $this->createEmailBody(1),
                    $this->createEmailBody(5),
                ],
                ['--id=1', '--id=5'],
            ],
        ];
    }

    protected function createEmailBody($emailId)
    {
        $email = new Email();
        $email;

        $emailRef = new ReflectionClass(get_class($email));
        $id = $emailRef->getProperty('id');
        $id->setAccessible(true);
        $id->setValue($email, $emailId);
        $id->setAccessible(false);

        $emailBody = new EmailBody();
        $emailBody->setEmail($email);

        return $emailBody;
    }
}
