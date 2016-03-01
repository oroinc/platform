<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use ReflectionClass;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\EventListener\AutoResponseListener;

class AutoResponseListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $autoResponseManager;
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;
    protected $uow;

    protected $autoResponseListener;

    public function setUp()
    {
        $this->autoResponseManager =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Manager\AutoResponseManager')
                ->disableOriginalConstructor()
                ->getMock();

        $autoResponseManagerLink =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
                ->disableOriginalConstructor()
                ->getMock();
        $autoResponseManagerLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->autoResponseManager));

        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->autoResponseListener = new AutoResponseListener($autoResponseManagerLink);
    }

    /**
     * @dataProvider testProvider
     */
    public function testListenerShouldNotFlushJobIfRulesDoesntExists(array $entityInsertions, array $expectedArgs)
    {
        $this->autoResponseManager
            ->expects($this->exactly(count($entityInsertions)))
            ->method('hasAutoResponses')
            ->will($this->returnValue(false));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($entityInsertions));

        $this->em->expects($this->never())
            ->method('persist')
            ->with(new \PHPUnit_Framework_Constraint_IsInstanceOf('JMS\JobQueueBundle\Entity\Job'));

        $this->autoResponseListener->onFlush(new OnFlushEventArgs($this->em));
        $this->autoResponseListener->postFlush(new PostFlushEventArgs($this->em));
    }

    /**
     * @dataProvider testProvider
     */
    public function testListenerShouldFlushJobIfRulesExists(array $entityInsertions, array $expectedArgs)
    {
        $this->autoResponseManager
            ->expects($this->exactly(count($entityInsertions)))
            ->method('hasAutoResponses')
            ->will($this->returnValue(true));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($entityInsertions));

        $this->em->expects($this->once())
            ->method('persist')
            ->with(new \PHPUnit_Framework_Constraint_IsInstanceOf('JMS\JobQueueBundle\Entity\Job'));

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
