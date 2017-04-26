<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity as OID;

use Oro\Bundle\EmailBundle\Acl\Voter\EmailVoter;
use Oro\Bundle\EmailBundle\EventListener\RoleSubscriber;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Component\DependencyInjection\ServiceLink;

class RoleSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var RoleSubscriber */
    protected $roleSubscriber;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
    protected $unitOfWork;

    /** @var AclManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $aclManager;

    protected function setUp()
    {
        $this->aclManager = $this->createMock(AclManager::class);

        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->expects($this->any())->method('getUnitOfWork')->willReturn($this->unitOfWork);

        /** @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject $serviceLink */
        $serviceLink = $this->createMock(ServiceLink::class);
        $serviceLink->expects($this->any())->method('getService')->willReturn($this->aclManager);
        $this->roleSubscriber = new RoleSubscriber($serviceLink);
    }

    public function testGetSubscribedEvents()
    {
        self::assertEquals([Events::onFlush, Events::postFlush], $this->roleSubscriber->getSubscribedEvents());
    }

    public function testOnFlush()
    {
        $this->unitOfWork->expects($this->exactly(2))
            ->method('getScheduledEntityInsertions')
            ->willReturnOnConsecutiveCalls([
                new \stdClass(),
                new Role(),
                new \stdClass(),
                new Role(),
            ], [
                new Role(),
                new \stdClass(),
            ]);

        $this->aclManager->expects($this->any())
            ->method('getMaskBuilder')
            ->willReturn($this->createMock(MaskBuilder::class));

        // Number of entities of type Role
        $this->aclManager->expects($this->exactly(3))
            ->method('getSid')
            ->willReturn($this->createMock(SID::class));

        $this->aclManager->expects($this->any())
            ->method('getOid')
            ->willReturn(new OID('identifier', 'type'));

        $this->roleSubscriber->onFlush(new OnFlushEventArgs($this->em));
        $this->roleSubscriber->onFlush(new OnFlushEventArgs($this->em));
        $this->roleSubscriber->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testPostFlushWithEmptyRoles()
    {
        $this->unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')->willReturn([]);
        $this->roleSubscriber->onFlush(new OnFlushEventArgs($this->em));

        $this->aclManager->expects($this->never())->method('getSid');
        $this->roleSubscriber->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testPostFlush()
    {
        $roles = [
            $role1 = new Role(),
            $role2 = new Role(),
            $role3 = new Role(),
        ];

        $this->unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')->willReturn($roles);
        $this->roleSubscriber->onFlush(new OnFlushEventArgs($this->em));

        $sids = [];
        for ($i = 0, $rolesCount = count($roles); $i < $rolesCount; $i++) {
            $sids[] = $this->createMock(SID::class);
        }

        $this->configureAclManager($roles, $sids);

        $this->roleSubscriber->postFlush(new PostFlushEventArgs($this->em));
    }

    /**
     * @param array $roles
     * @param array $sids
     */
    protected function configureAclManager(array $roles, array $sids)
    {
        $this->aclManager->expects($this->exactly(3))
            ->method('getSid')
            ->withConsecutive(...$roles)
            ->willReturnOnConsecutiveCalls(...$sids);

        $this->aclManager->expects($this->exactly(9))
            ->method('getOid')
            ->with($this->callback(function ($classIdentifier) {
                foreach (EmailVoter::SUPPORTED_CLASSES as $className) {
                    if ($classIdentifier === 'entity:' . $className) {
                        return true;
                    }
                }

                return false;
            }))
            ->willReturnCallback(function ($val) {
                return new OID($val, 'type');
            });


        $masks = [];
        $this->aclManager->expects($this->exactly(27))
            ->method('getMaskBuilder')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf(OID::class),
                    $this->callback(function (OID $oid) {
                        $oidIdentifier = $oid->getIdentifier();
                        foreach (EmailVoter::SUPPORTED_CLASSES as $className) {
                            if ($oidIdentifier === 'entity:' . $className) {
                                return true;
                            }
                        }

                        return false;
                    })
                ),
                $this->callback(function ($permission) {
                    return in_array($permission, ['VIEW', 'CREATE', 'EDIT'], true);
                })
            )
            ->willReturnCallback(function (OID $oid, $permission) use (&$masks) {
                $maskBuilder = $this->createMock(MaskBuilder::class);
                $maskBuilder->expects($this->once())->method('add')->with($permission . '_SYSTEM');

                $maskHash = $this->getMaskHash($oid);
                $maskToReturn = isset($masks[$maskHash]) ? $masks[$maskHash] << 1 : 1;
                $maskBuilder->expects($this->atLeastOnce())->method('get')->willReturn($maskToReturn);
                $masks[$maskHash] = $maskToReturn;

                return $maskBuilder;
            });

        $this->aclManager->expects($this->exactly(9))
            ->method('setPermission')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf(SID::class),
                    $this->callback(function ($sid) use ($sids) {
                        return in_array($sid, $sids, true);
                    })
                ),
                $this->logicalAnd(
                    $this->isInstanceOf(OID::class),
                    $this->callback(function (OID $oid) {
                        $oidIdentifier = $oid->getIdentifier();
                        foreach (EmailVoter::SUPPORTED_CLASSES as $className) {
                            if ($oidIdentifier === 'entity:' . $className) {
                                return true;
                            }
                        }

                        return false;
                    })
                )
            )
            ->willReturnCallback(function (SID $sid, OID $oid, $mask) use (&$masks) {
                $maskHash = $this->getMaskHash($oid);
                $this->assertArrayHasKey($maskHash, $masks, 'Mask for OID "' . (string)$oid . '" not found"');

                // $masks[$maskHash] has last added bit.
                // To get full mask we need to shift it on 1 bit and subtract 1
                // Ex. 00010000
                // $mask << 1 = 00100000
                // ($mask << 1) - 1 = 00011111
                $expectedMask = ($masks[$maskHash] << 1) - 1;

                $this->assertEquals($expectedMask, $mask, 'Incorrect mask calculation');
            });

        $this->aclManager->expects($this->once())->method('flush');
        $this->roleSubscriber->postFlush(new PostFlushEventArgs($this->em));
    }

    /**
     * @param OID $oid
     * @return string
     */
    protected function getMaskHash(OID $oid)
    {
        return spl_object_hash($oid);
    }
}
