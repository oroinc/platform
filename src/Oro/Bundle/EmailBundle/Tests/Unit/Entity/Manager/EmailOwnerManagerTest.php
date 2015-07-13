<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmail;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailAddressProxy;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwnerWithoutEmail;

class EmailOwnerManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $uow;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHandleOnFlush()
    {
        $this->initOnFlush();

        $emailAddrManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager          = new EmailOwnerManager(
            $this->getEmailOwnerProviderStorage(),
            $emailAddrManager
        );

        $owner1         = new TestEmailOwner(1);
        $owner2         = new TestEmailOwner(2);
        $owner3         = new TestEmailOwner(3);
        $owner4         = new TestEmailOwnerWithoutEmail(4);
        $newOwner1      = new TestEmailOwner(null, 'newOwner1');
        $newOwner2      = new TestEmailOwner(null, 'newOwner2');
        $deletingOwner1 = new TestEmailOwner(100);
        $deletingOwner2 = new TestEmailOwnerWithoutEmail(100);
        $deletingOwner3 = new TestEmailOwner(200);

        $owner1NewEmail = new TestEmail(null, $owner1);
        $owner2Email    = new TestEmail(1, $owner2);
        $deletingEmail1 = new TestEmail(2, $owner1, 'deleting_email1');

        $owner1OldPrimaryEmailAddr    = new TestEmailAddressProxy($owner1);
        $owner1NewPrimaryEmailAddr    = new TestEmailAddressProxy();
        $owner1OldHomeEmailAddr       = new TestEmailAddressProxy($owner1);
        $owner1NewHomeEmailAddr       = new TestEmailAddressProxy($owner2);
        $owner2NewPrimaryEmailAddr    = new TestEmailAddressProxy($owner1);
        $owner3OldPrimaryEmailAddr    = new TestEmailAddressProxy($owner3);
        $newOwner2NewPrimaryEmailAddr = new TestEmailAddressProxy();

        $owner1NewEmailAddr = new TestEmailAddressProxy();
        $owner2EmailAddr    = new TestEmailAddressProxy($owner1);

        $deletingOwner2EmailAddr  = new TestEmailAddressProxy($deletingOwner2);
        $deletingOwner3EmailAddr1 = new TestEmailAddressProxy($deletingOwner3);
        $deletingOwner3EmailAddr2 = new TestEmailAddressProxy($deletingOwner3);
        $deletingEmail1EmailAddr  = new TestEmailAddressProxy($deletingEmail1->getEmailOwner());

        $scheduledEntityInsertions = [
            $newOwner1,
            $newOwner2,
            $owner1NewEmail
        ];
        $scheduledEntityUpdates    = [
            $owner1,
            $owner2,
            $owner3,
            $owner4,
            $owner2Email
        ];
        $scheduledEntityDeletions  = [
            $deletingOwner1,
            $deletingOwner2,
            $deletingOwner3,
            $deletingEmail1
        ];

        $this->uow->expects($this->any())
            ->method('getEntityChangeSet')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            $owner1,
                            [
                                'name'         => ['old_name', 'new_name'],
                                'primaryEmail' => ['old_email1', 'new_email1'],
                                'homeEmail'    => ['old_home_email1', 'new_home_email1'],
                            ]
                        ],
                        [$owner2, ['primaryEmail' => [null, 'new_email2']]],
                        [$owner3, ['primaryEmail' => ['old_email3', null]]],
                        [$owner4, ['primaryEmail' => ['old_email4', 'new_email4']]],
                        [$newOwner1, ['primaryEmail' => [null, null]]],
                        [$newOwner2, ['primaryEmail' => [null, 'new_email20']]],
                        [$owner1NewEmail, ['email' => [null, 'new_email1_1']]],
                        [$owner2Email, ['email' => ['new_email1_1', 'new_email2_1']]],
                    ]
                )
            );
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($scheduledEntityInsertions));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue($scheduledEntityUpdates));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue($scheduledEntityDeletions));

        $emailAddrRepo = $this->createEntityRepositoryMock();
        $emailAddrManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->will($this->returnValue($emailAddrRepo));

        $emailAddrManager->expects($this->any())
            ->method('newEmailAddress')
            ->will(
                $this->onConsecutiveCalls(
                    $newOwner2NewPrimaryEmailAddr,
                    $owner1NewPrimaryEmailAddr,
                    $owner1NewEmailAddr
                )
            );

        $emailAddrRepo->expects($this->any())
            ->method('findOneBy')
            ->will(
                $this->returnCallback(
                //@codingStandardsIgnoreStart
                    function ($criteria) use (
                        $owner1OldPrimaryEmailAddr,
                        $owner1OldHomeEmailAddr,
                        $owner1NewHomeEmailAddr,
                        $owner2NewPrimaryEmailAddr,
                        $owner2EmailAddr,
                        $owner3OldPrimaryEmailAddr,
                        $deletingEmail1EmailAddr
                    ) {
                        //@codingStandardsIgnoreEnd
                        switch ($criteria['email']) {
                            case 'old_email1':
                                return $owner1OldPrimaryEmailAddr;
                            case 'old_home_email1':
                                return $owner1OldHomeEmailAddr;
                            case 'new_home_email1':
                                return $owner1NewHomeEmailAddr;
                            case 'new_email2':
                                return $owner2NewPrimaryEmailAddr;
                            case 'new_email2_1':
                                return $owner2EmailAddr;
                            case 'old_email3':
                                return $owner3OldPrimaryEmailAddr;
                            case 'deleting_email1':
                                return $deletingEmail1EmailAddr;
                            default:
                                return null;
                        }
                    }
                )
            );
        $emailAddrRepo->expects($this->any())
            ->method('findBy')
            ->will(
                $this->returnCallback(
                //@codingStandardsIgnoreStart
                    function ($criteria) use (
                        $deletingOwner1,
                        $deletingOwner2,
                        $deletingOwner3,
                        $deletingOwner2EmailAddr,
                        $deletingOwner3EmailAddr1,
                        $deletingOwner3EmailAddr2
                    ) {
                        //@codingStandardsIgnoreEnd
                        if ($criteria == ['owner1' => $deletingOwner1]) {
                            return [];
                        } elseif ($criteria == ['owner2' => $deletingOwner2]) {
                            return [$deletingOwner2EmailAddr];
                        } elseif ($criteria == ['owner1' => $deletingOwner3]) {
                            return [$deletingOwner3EmailAddr1, $deletingOwner3EmailAddr2];
                        }

                        return [];
                    }
                )
            );

        $this->uow->expects($this->exactly(13))
            ->method('computeChangeSet')
            ->with(
                $this->anything(),
                $this->logicalOr(
                    $this->identicalTo($owner1OldPrimaryEmailAddr),
                    $this->identicalTo($owner1NewPrimaryEmailAddr),
                    $this->identicalTo($owner1OldHomeEmailAddr),
                    $this->identicalTo($owner1NewHomeEmailAddr),
                    $this->identicalTo($owner1NewEmailAddr),
                    $this->identicalTo($owner2NewPrimaryEmailAddr),
                    $this->identicalTo($owner2EmailAddr),
                    $this->identicalTo($owner3OldPrimaryEmailAddr),
                    $this->identicalTo($newOwner2NewPrimaryEmailAddr),
                    $this->identicalTo($deletingOwner2EmailAddr),
                    $this->identicalTo($deletingOwner3EmailAddr1),
                    $this->identicalTo($deletingOwner3EmailAddr2),
                    $this->identicalTo($deletingEmail1EmailAddr)
                )
            );

        $manager->handleOnFlush($this->createOnFlushEventArgsMock());

        $this->assertNull($owner1OldPrimaryEmailAddr->getOwner());
        $this->assertSame($owner1, $owner1NewPrimaryEmailAddr->getOwner());
        $this->assertNull($owner1OldHomeEmailAddr->getOwner());
        $this->assertSame($owner1, $owner1NewHomeEmailAddr->getOwner());
        $this->assertSame($owner1, $owner1NewEmailAddr->getOwner());
        $this->assertSame($owner2, $owner2NewPrimaryEmailAddr->getOwner());
        $this->assertSame($owner2, $owner2EmailAddr->getOwner());
        $this->assertNull($owner3OldPrimaryEmailAddr->getOwner());
        $this->assertSame($newOwner2, $newOwner2NewPrimaryEmailAddr->getOwner());
        $this->assertNull($deletingOwner2EmailAddr->getOwner());
        $this->assertNull($deletingOwner3EmailAddr1->getOwner());
        $this->assertNull($deletingOwner3EmailAddr2->getOwner());
        $this->assertNull($deletingEmail1EmailAddr->getOwner());
    }

    private function initOnFlush()
    {
        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $testEmailAddressProxyMetadata      = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $testEmailOwnerMetadata             = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $testEmailOwnerWithoutEmailMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailAddressProxy',
                            $testEmailAddressProxyMetadata
                        ],
                        [
                            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner',
                            $testEmailOwnerMetadata
                        ],
                        [
                            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwnerWithoutEmail',
                            $testEmailOwnerWithoutEmailMetadata
                        ],
                    ]
                )
            );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createOnFlushEventArgsMock()
    {
        $flushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $flushEventArgs->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($this->em));

        return $flushEventArgs;
    }

    private function getEmailOwnerProviderStorage()
    {
        $provider1 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $provider1->expects($this->any())
            ->method('getEmailOwnerClass')
            ->will(
                $this->returnValue(
                    'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner'
                )
            );
        $provider2 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $provider2->expects($this->any())
            ->method('getEmailOwnerClass')
            ->will(
                $this->returnValue(
                    'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwnerWithoutEmail'
                )
            );

        $storage = new EmailOwnerProviderStorage();
        $storage->addProvider($provider1);
        $storage->addProvider($provider2);

        return $storage;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createEntityRepositoryMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
