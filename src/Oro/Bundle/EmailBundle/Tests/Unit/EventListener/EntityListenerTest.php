<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EntitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailOwnerManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailActivityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailThreadManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailOwnersProvider;

    /** @var EmailOwnerProviderStorage */
    private $emailOwnerStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $userEmailOwnerProvider;

    /** @var ActivityListChainProvider */
    private $chainProvider;

    protected function setUp()
    {
        $this->emailOwnerManager    =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailActivityManager =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailThreadManager =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
                ->setMethods(['getRepository', 'getEmailsByOwnerEntity'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->userEmailOwnerProvider = $this
            ->getMockBuilder('Oro\Bundle\UserBundle\Entity\Provider\EmailOwnerProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->chainProvider =
            $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
                ->disableOriginalConstructor()->getMock();

        $this->emailOwnerStorage = new EmailOwnerProviderStorage();
        $this->emailOwnerStorage->addProvider($this->userEmailOwnerProvider);

        $this->emailOwnersProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider')
            ->setConstructorArgs([$this->chainProvider, $this->emailOwnerStorage, $this->registry])
            ->setMethods(['supportOwnerProvider'])
            ->getMock();

        $this->listener = new EntityListener(
            $this->emailOwnerManager,
            $this->emailActivityManager,
            $this->emailThreadManager,
            $this->emailOwnersProvider
        );
    }

    public function testOnFlush()
    {
        $contactsArray = [new User(), new User(), new User()];
        $emailsArray = [new Email(), new Email(), new Email()];

        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->setMethods(['getEntityManager', 'getUnitOfWork', 'getScheduledEntityInsertions'])
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($onFlushEventArgs));
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($onFlushEventArgs));
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($contactsArray));

        $this->emailOwnerManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($onFlushEventArgs));
        $this->emailActivityManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($onFlushEventArgs));
        $this->emailThreadManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($onFlushEventArgs));

        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->setMethods(['getEntityManager', 'flush'])
            ->disableOriginalConstructor()
            ->getMock();
        $postFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($postFlushEventArgs));

        $this->registry
            ->expects($this->exactly(3))
            ->method('getRepository')
            ->will($this->returnValue($this->registry));
        $this->registry
            ->expects($this->exactly(3))
            ->method('getEmailsByOwnerEntity')
            ->will($this->returnValue($emailsArray));
        $this->emailOwnersProvider
            ->expects($this->exactly(3))
            ->method('supportOwnerProvider')
            ->will($this->returnValue(true));
        $this->chainProvider
            ->expects($this->exactly(3))
            ->method('isSupportedTargetEntity')
            ->will($this->returnValue(true));
        $this->userEmailOwnerProvider
            ->expects($this->exactly(3))
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(ClassUtils::getClass(new User)));

        $this->emailActivityManager
            ->expects($this->exactly(9))
            ->method('addAssociation');
        $postFlushEventArgs
            ->expects($this->once())
            ->method('flush');
        
        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }

    public function testOnFlushNotSupported()
    {
        $contactsArray = [new User(), new User(), new User()];
        $emailsArray = [new Email(), new Email(), new Email()];

        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->setMethods(['getEntityManager', 'getUnitOfWork', 'getScheduledEntityInsertions'])
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($onFlushEventArgs));
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($onFlushEventArgs));
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($contactsArray));

        $this->emailOwnerManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($onFlushEventArgs));
        $this->emailActivityManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($onFlushEventArgs));
        $this->emailThreadManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($onFlushEventArgs));

        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->setMethods(['getEntityManager', 'flush'])
            ->disableOriginalConstructor()
            ->getMock();
        $postFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($postFlushEventArgs));

        $this->registry
            ->expects($this->never())
            ->method('getRepository')
            ->will($this->returnValue($this->registry));
        $this->registry
            ->expects($this->never())
            ->method('getEmailsByOwnerEntity')
            ->will($this->returnValue($emailsArray));
        $this->emailOwnersProvider
            ->expects($this->exactly(3))
            ->method('supportOwnerProvider')
            ->will($this->returnValue(true));
        $this->chainProvider
            ->expects($this->exactly(3))
            ->method('isSupportedTargetEntity')
            ->will($this->returnValue(false));
        $this->userEmailOwnerProvider
            ->expects($this->never())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(ClassUtils::getClass(new User)));

        $this->emailActivityManager
            ->expects($this->never())
            ->method('addAssociation');
        $postFlushEventArgs
            ->expects($this->once())
            ->method('flush');

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }
}
