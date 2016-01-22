<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\TestUtils\Mocks\ServiceLink;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EntityListenerTest extends \PHPUnit_Framework_TestCase
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
            new ServiceLink($this->emailActivityManager),
            new ServiceLink($this->emailThreadManager),
            $this->emailOwnersProvider
        );
    }

    public function testOnFlush()
    {
        $contactsArray = [new User(), new User(), new User()];

        $uow = new UnitOfWork();
        array_map([$uow, 'addInsertion'], $contactsArray);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $em
            ->expects($this->once())
            ->method('flush');
        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->with($this->identicalTo($uow))
            ->will($this->returnValue([]));
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->with([])
            ->will($this->returnValue([]));

        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $postFlushEventArgs
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailOwnersProvider
            ->expects($this->exactly(3))
            ->method('supportOwnerProvider')
            ->will($this->returnValue(true));

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }

    public function testOnFlushNotSupported()
    {
        $contactsArray = [new User(), new User(), new User()];
        $emailsArray = [new Email(), new Email(), new Email()];

        $uow = new UnitOfWork();
        array_map([$uow, 'addInsertion'], array_merge($contactsArray, $emailsArray));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->will($this->returnValue([]));
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->will($this->returnValue([]));
        $this->emailActivityManager->expects($this->once())
            ->method('updateActivities')
            ->with([
                spl_object_hash($emailsArray[0]) => $emailsArray[0],
                spl_object_hash($emailsArray[1]) => $emailsArray[1],
                spl_object_hash($emailsArray[2]) => $emailsArray[2],
            ]);

        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $postFlushEventArgs
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->registry
            ->expects($this->never())
            ->method('getRepository')
            ->will($this->returnValue($this->registry));
        $this->registry
            ->expects($this->never())
            ->method('getEmailsByOwnerEntity')
            ->will($this->returnValue($emailsArray));
        $this->emailOwnersProvider
            ->expects($this->exactly(6))
            ->method('supportOwnerProvider')
            ->will($this->returnValue(true));
        $this->userEmailOwnerProvider
            ->expects($this->never())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(ClassUtils::getClass(new User)));

        $this->emailActivityManager
            ->expects($this->never())
            ->method('addAssociation');

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }
}
