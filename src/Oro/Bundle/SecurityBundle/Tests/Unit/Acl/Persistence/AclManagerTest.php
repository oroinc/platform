<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AceManipulationHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\Batch\BatchItem;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclManagerTest extends TestCase
{
    private ObjectIdentityFactory&MockObject $objectIdentityFactory;
    private MutableAclProvider&MockObject $aclProvider;
    private AceManipulationHelper&MockObject $aceProvider;
    private AclExtensionInterface&MockObject $extension;
    private FieldAclExtension&MockObject $fieldExtension;
    private AclExtensionSelector&MockObject $extensionSelector;
    private AclManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->objectIdentityFactory = $this->createMock(ObjectIdentityFactory::class);
        $this->aclProvider = $this->createMock(MutableAclProvider::class);
        $this->aceProvider = $this->createMock(AceManipulationHelper::class);
        $this->extension = $this->createMock(AclExtensionInterface::class);
        $this->fieldExtension = $this->createMock(FieldAclExtension::class);
        $this->extensionSelector = $this->createMock(AclExtensionSelector::class);

        $this->extension->expects($this->any())
            ->method('getExtensionKey')
            ->willReturn('entity');
        $this->extension->expects($this->any())
            ->method('getFieldExtension')
            ->willReturn($this->fieldExtension);
        $this->extension->expects($this->any())
            ->method('getServiceBits')
            ->willReturn(0);

        $this->extensionSelector->expects($this->any())
            ->method('select')
            ->willReturn($this->extension);

        $this->manager = new AclManager(
            $this->objectIdentityFactory,
            $this->extensionSelector,
            $this->aclProvider,
            $this->aceProvider
        );
    }

    public function testIsAclEnabled(): void
    {
        $factory = $this->createMock(ObjectIdentityFactory::class);
        $extensionSelector = $this->createMock(AclExtensionSelector::class);
        $manager = new AclManager($factory, $extensionSelector);

        $this->assertFalse($manager->isAclEnabled());

        $aclProvider = $this->createMock(MutableAclProvider::class);
        $manager = new AclManager($factory, $extensionSelector, $aclProvider);

        $this->assertTrue($manager->isAclEnabled());
    }

    public function testGetOid(): void
    {
        $oid = new ObjectIdentity('test', 'test');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test'))
            ->willReturn($oid);

        $this->assertSame($oid, $this->manager->getOid('test'));
    }

    public function testGetRootOid(): void
    {
        $oid = new ObjectIdentity('test', 'test');
        $this->objectIdentityFactory->expects($this->once())
            ->method('root')
            ->with($this->equalTo('test'))
            ->willReturn($oid);

        $this->assertSame($oid, $this->manager->getRootOid('test'));
    }

    public function testDeleteAclShouldNotFailIfNoItems(): void
    {
        $oid = new ObjectIdentity('test', 'test');
        $this->manager->deleteAcl($oid);
    }

    public function testDeleteAclShouldMarkItemAsToDelete(): void
    {
        $oid = new ObjectIdentity('test', 'test');

        $this->setItem($oid, BatchItem::STATE_NONE);

        $this->manager->deleteAcl($oid);

        /** @var BatchItem[] $items */
        $items = ReflectionUtil::getPropertyValue($this->manager, 'items');
        $this->assertCount(1, $items);
        $this->assertEquals(BatchItem::STATE_DELETE, current($items)->getState());
    }

    public function testSetPermissionForNewAclIfGetAcesCalledBefore(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willThrowException(new AclNotFoundException());
        $this->extension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->never())
            ->method('getAces');
        $this->aceProvider->expects($this->never())
            ->method('setPermission');

        $this->manager->getAces($sid, $oid);
        $this->manager->setPermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testSetPermissionForRootOid(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->extension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->once())
            ->method('setPermission')
            ->with(
                $this->identicalTo($acl),
                $this->identicalTo($this->extension),
                $this->equalTo(true),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            );

        $this->manager->setPermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testSetPermissionForDomainObject(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->extension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->once())
            ->method('setPermission')
            ->with(
                $this->identicalTo($acl),
                $this->identicalTo($this->extension),
                $this->equalTo(true),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            );

        $this->manager->setPermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testSetPermissionForEntityClass(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->extension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->once())
            ->method('setPermission')
            ->with(
                $this->identicalTo($acl),
                $this->identicalTo($this->extension),
                $this->equalTo(true),
                $this->equalTo(AclManager::CLASS_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            )
            ->willReturn(true);

        $this->manager->setPermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testSetFieldPermissionForRootOid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $this->manager->setFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testSetFieldPermissionForDomainObject(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->fieldExtension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->once())
            ->method('setPermission')
            ->with(
                $this->identicalTo($acl),
                $this->identicalTo($this->fieldExtension),
                $this->equalTo(true),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo($field),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            );

        $this->manager->setFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testSetFieldPermissionForEntityClass(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->fieldExtension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->once())
            ->method('setPermission')
            ->with(
                $this->identicalTo($acl),
                $this->identicalTo($this->fieldExtension),
                $this->equalTo(true),
                $this->equalTo(AclManager::CLASS_ACE),
                $this->equalTo($field),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            )
            ->willReturn(true);

        $this->manager->setFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testDeletePermissionForRootOid(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deletePermission')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            );

        $this->manager->deletePermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testDeletePermissionForDomainObject(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deletePermission')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            );

        $this->manager->deletePermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testDeletePermissionForEntityClass(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deletePermission')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::CLASS_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            )
            ->willReturn(true);

        $this->manager->deletePermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testDeleteFieldPermissionForRootOid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $this->manager->deleteFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testDeleteFieldPermissionForDomainObject(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deletePermission')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo($field),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            );

        $this->manager->deleteFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testDeleteFieldPermissionForEntityClass(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deletePermission')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::CLASS_ACE),
                $this->equalTo($field),
                $this->identicalTo($sid),
                $this->equalTo($granting),
                $this->equalTo($mask),
                $this->equalTo($strategy)
            )
            ->willReturn(true);

        $this->manager->deleteFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testDeleteAllPermissionsForRootOid(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deleteAllPermissions')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid)
            );

        $this->manager->deleteAllPermissions($sid, $oid);
    }

    public function testDeleteAllPermissionsForDomainObject(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deleteAllPermissions')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid)
            );

        $this->manager->deleteAllPermissions($sid, $oid);
    }

    public function testDeleteAllPermissionsForEntityClass(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deleteAllPermissions')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::CLASS_ACE),
                $this->equalTo(null),
                $this->identicalTo($sid)
            )
            ->willReturn(true);

        $this->manager->deleteAllPermissions($sid, $oid);
    }

    public function testDeleteAllFieldPermissionsForRootOid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $field = 'TestField';

        $this->manager->deleteAllFieldPermissions($sid, $oid, $field);
    }

    public function testDeleteAllFieldPermissionsForDomainObject(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $field = 'TestField';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deleteAllPermissions')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo($field),
                $this->identicalTo($sid)
            );

        $this->manager->deleteAllFieldPermissions($sid, $oid, $field);
    }

    public function testDeleteAllFieldPermissionsForEntityClass(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $field = 'TestField';

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willReturn($acl);
        $this->aceProvider->expects($this->once())
            ->method('deleteAllPermissions')
            ->with(
                $this->identicalTo($acl),
                $this->equalTo(AclManager::CLASS_ACE),
                $this->equalTo($field),
                $this->identicalTo($sid)
            )
            ->willReturn(true);

        $this->manager->deleteAllFieldPermissions($sid, $oid, $field);
    }

    public function testSetPermissionForRootOidNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willThrowException(new AclNotFoundException());
        $this->extension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->never())
            ->method('setPermission');

        $this->manager->setPermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testSetPermissionForDomainObjectNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willThrowException(new AclNotFoundException());
        $this->extension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->never())
            ->method('setPermission');

        $this->manager->setPermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testSetPermissionForEntityClassNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willThrowException(new AclNotFoundException());
        $this->extension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->never())
            ->method('setPermission');

        $this->manager->setPermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testSetFieldPermissionForDomainObjectNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willThrowException(new AclNotFoundException());
        $this->fieldExtension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->never())
            ->method('setPermission');

        $this->manager->setFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testSetFieldPermissionForEntityClassNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with($this->identicalTo($oid))
            ->willThrowException(new AclNotFoundException());
        $this->fieldExtension->expects($this->once())
            ->method('validateMask')
            ->with($this->equalTo($mask), $this->identicalTo($oid));
        $this->aceProvider->expects($this->never())
            ->method('setPermission');

        $this->manager->setFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testDeletePermissionForRootOidNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deletePermission');

        $this->manager->deletePermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testDeletePermissionForDomainObjectNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deletePermission');

        $this->manager->deletePermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testDeletePermissionForEntityClassNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deletePermission');

        $this->manager->deletePermission($sid, $oid, $mask, $granting, $strategy);
    }

    public function testDeleteFieldPermissionForDomainObjectNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deletePermission');

        $this->manager->deleteFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testDeleteFieldPermissionForEntityClassNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        $field = 'TestField';

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deletePermission');

        $this->manager->deleteFieldPermission($sid, $oid, $field, $mask, $granting, $strategy);
    }

    public function testDeleteAllPermissionsForRootOidNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deleteAllPermissions');

        $this->manager->deleteAllPermissions($sid, $oid);
    }

    public function testDeleteAllPermissionsForDomainObjectNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deleteAllPermissions');

        $this->manager->deleteAllPermissions($sid, $oid);
    }

    public function testDeleteAllPermissionsForEntityClassNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deleteAllPermissions');

        $this->manager->deleteAllPermissions($sid, $oid);
    }

    public function testDeleteAllFieldPermissionsForDomainObjectNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity(123, 'Acme\Test');
        $field = 'TestField';

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deleteAllPermissions');

        $this->manager->deleteAllFieldPermissions($sid, $oid, $field);
    }

    public function testDeleteAllFieldPermissionsForEntityClassNoAcl(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $oid = new ObjectIdentity('entity', 'Acme\Test');
        $field = 'TestField';

        $this->setItem($oid, BatchItem::STATE_CREATE);

        $this->aclProvider->expects($this->never())
            ->method('findAcl');
        $this->aceProvider->expects($this->never())
            ->method('deleteAllPermissions');

        $this->manager->deleteAllFieldPermissions($sid, $oid, $field);
    }

    public function testFlush(): void
    {
        $oid1 = new ObjectIdentity('Acme\Test1', 'entity');
        $oid2 = new ObjectIdentity('Acme\Test2', 'entity');
        $oid3 = new ObjectIdentity('Acme\Test3', 'entity');
        $oid4 = new ObjectIdentity('Acme\Test4', 'entity');

        $newItemSid = $this->createMock(SecurityIdentityInterface::class);
        $newItem = new BatchItem($oid2, BatchItem::STATE_CREATE);
        $newItem->addAce(AclManager::OBJECT_ACE, 'TestField', $newItemSid, true, 123, 'all', true);

        $updateItemAcl = $this->createMock(MutableAclInterface::class);
        $deleteItemAcl = $this->createMock(MutableAclInterface::class);

        $this->setItems([
            new BatchItem($oid1, BatchItem::STATE_NONE),
            $newItem,
            new BatchItem($oid3, BatchItem::STATE_UPDATE, $updateItemAcl),
            new BatchItem($oid4, BatchItem::STATE_DELETE, $deleteItemAcl),
        ]);

        $this->aclProvider->expects($this->once())
            ->method('beginTransaction');
        $this->aclProvider->expects($this->once())
            ->method('commit');

        $acl = $this->createMock(MutableAclInterface::class);
        $this->aclProvider->expects($this->once())
            ->method('createAcl')
            ->with($this->identicalTo($oid2))
            ->willReturn($acl);

        $this->aceProvider->expects($this->once())
            ->method('setPermission')
            ->with(
                $this->identicalTo($acl),
                $this->identicalTo($this->extension),
                $this->equalTo(true),
                $this->equalTo(AclManager::OBJECT_ACE),
                $this->equalTo('TestField'),
                $this->identicalTo($newItemSid),
                $this->equalTo(true),
                $this->equalTo(123),
                $this->equalTo('all')
            )
            ->willReturn(true);

        $this->aclProvider->expects($this->exactly(2))
            ->method('updateAcl');

        $this->aclProvider->expects($this->once())
            ->method('deleteAcl')
            ->with($this->identicalTo($oid4));

        $this->manager->flush();
    }

    private function setItem(ObjectIdentity $oid, $state, ?MutableAclInterface $acl = null)
    {
        $key = ReflectionUtil::callMethod($this->manager, 'getKey', [$oid]);
        ReflectionUtil::setPropertyValue(
            $this->manager,
            'items',
            [$key => new BatchItem($oid, $state, $acl)]
        );
    }

    /**
     * @param BatchItem[] $items
     */
    private function setItems(array $items)
    {
        $val = [];
        foreach ($items as $item) {
            $key = ReflectionUtil::callMethod($this->manager, 'getKey', [$item->getOid()]);
            $val[$key] = $item;
        }
        ReflectionUtil::setPropertyValue($this->manager, 'items', $val);
    }
}
