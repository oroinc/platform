<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy;
use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootAclWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityToStringConverter;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RootBasedAclWrapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var Acl|\PHPUnit\Framework\MockObject\MockObject */
    private $acl;

    /** @var Acl|\PHPUnit\Framework\MockObject\MockObject */
    private $rootAcl;

    protected function setUp(): void
    {
        $this->acl = $this->createMock(Acl::class);
        $this->rootAcl = $this->createMock(Acl::class);
    }

    public function testGetClassAces()
    {
        $context = $this->createMock(PermissionGrantingStrategyContextInterface::class);
        $aclExtension = $this->createMock(AclExtensionInterface::class);
        $permissionGrantingStrategy = $this->createMock(PermissionGrantingStrategy::class);
        $permissionGrantingStrategy->expects($this->any())
            ->method('getContext')
            ->willReturn($context);
        $context->expects($this->any())
            ->method('getAclExtension')
            ->willReturn($aclExtension);

        $acl = new Acl(
            1,
            new ObjectIdentity('Test\Entity1', 'entity'),
            $permissionGrantingStrategy,
            [],
            false
        );
        $rootAcl = new Acl(
            10,
            new ObjectIdentity('entity', '(root)'),
            $permissionGrantingStrategy,
            [],
            false
        );

        $sid1 = new RoleSecurityIdentity('sid1');
        $sid2 = new RoleSecurityIdentity('sid2');
        $sid3 = new RoleSecurityIdentity('sid3');
        $sid4 = new RoleSecurityIdentity('sid4');

        $obj = new RootBasedAclWrapper(
            $acl,
            new RootAclWrapper($rootAcl, new SecurityIdentityToStringConverter())
        );
        $acl->insertClassAce($sid1, 1, 0); // new ACE
        $acl->insertClassAce($sid1, 256 + 1, 1); // new ACE, with service bits
        $acl->insertClassAce($sid2, 2, 2); // override root ACE
        $acl->insertClassAce($sid2, 256 + 2, 3); // override root ACE, with service bits
        $acl->insertClassAce($sid2, 256 * 3 + 2, 4); // new ACE for SID that have root ACEs
        $acl->insertClassAce($sid3, 4, 5); // new ACE, root ACL does not have ACE for this SID
        $rootAcl->insertObjectAce($sid2, 1, 0);
        $rootAcl->insertObjectAce($sid2, 256 + 1, 1);
        $rootAcl->insertObjectAce($sid2, 256 * 2 + 1, 2); // ACE existing only in root ACL
        $rootAcl->insertObjectAce($sid4, 8, 3); // ACL does not have ACE for this SID

        $aclExtension->expects($this->any())
            ->method('getServiceBits')
            ->willReturnCallback(function ($mask) {
                return $mask & (~255);
            });

        /** @var EntryInterface[] $resultAces */
        $resultAces = $obj->getClassAces();
        $resultMasks = [];
        foreach ($resultAces as $ace) {
            $resultMasks[] = $ace->getMask();
        }
        $this->assertEquals(
            [
                2,
                256 + 2,
                256 * 2 + 1,
                8,
                1,
                256 + 1,
                256 * 3 + 2,
                4
            ],
            $resultMasks
        );
    }

    public function testGetClassFieldAces()
    {
        $fieldName = 'testField';

        $context = $this->createMock(PermissionGrantingStrategyContextInterface::class);
        $aclExtension = $this->createMock(AclExtensionInterface::class);
        $permissionGrantingStrategy = $this->createMock(PermissionGrantingStrategy::class);
        $permissionGrantingStrategy->expects($this->any())
            ->method('getContext')
            ->willReturn($context);
        $context->expects($this->any())
            ->method('getAclExtension')
            ->willReturn($aclExtension);

        $acl = new Acl(
            1,
            new ObjectIdentity('Test\Entity1', 'entity'),
            $permissionGrantingStrategy,
            [],
            false
        );
        $rootAcl = new Acl(
            10,
            new ObjectIdentity('entity', '(root)'),
            $permissionGrantingStrategy,
            [],
            false
        );

        $sid1 = new RoleSecurityIdentity('sid1');
        $sid2 = new RoleSecurityIdentity('sid2');
        $sid3 = new RoleSecurityIdentity('sid3');
        $sid4 = new RoleSecurityIdentity('sid4');

        $obj = new RootBasedAclWrapper(
            $acl,
            new RootAclWrapper($rootAcl, new SecurityIdentityToStringConverter())
        );
        $acl->insertClassFieldAce($fieldName, $sid1, 1, 0); // new ACE
        $acl->insertClassFieldAce($fieldName, $sid1, 256 + 1, 1); // new ACE, with service bits
        $acl->insertClassFieldAce($fieldName, $sid2, 2, 2); // override root ACE
        $acl->insertClassFieldAce($fieldName, $sid2, 256 + 2, 3); // override root ACE, with service bits
        $acl->insertClassFieldAce($fieldName, $sid3, 4, 4); // new ACE, root ACL does not have ACE for this SID
        $rootAcl->insertObjectFieldAce($fieldName, $sid2, 1, 0);
        $rootAcl->insertObjectFieldAce($fieldName, $sid2, 256 + 1, 1);
        $rootAcl->insertObjectFieldAce($fieldName, $sid2, 256 * 2 + 1, 2); // ACE existing only in root ACL
        $rootAcl->insertObjectFieldAce($fieldName, $sid4, 8, 3); // ACL does not have ACE for this SID

        $aclExtension->expects($this->any())
            ->method('getServiceBits')
            ->willReturnCallback(function ($mask) {
                return $mask & (~255);
            });

        /** @var EntryInterface[] $resultAces */
        $resultAces = $obj->getClassFieldAces($fieldName);
        $resultMasks = [];
        foreach ($resultAces as $ace) {
            $resultMasks[] = $ace->getMask();
        }
        $this->assertEquals(
            [
                2,
                256 + 2,
                256 * 2 + 1,
                8,
                1,
                256 + 1,
                4
            ],
            $resultMasks
        );
    }

    public function testGetObjectAces()
    {
        $ace = $this->createMock(EntryInterface::class);

        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $this->acl->expects($this->once())
            ->method('getObjectAces')
            ->willReturn([$ace]);
        $result = $obj->getObjectAces();

        $this->assertEquals([$ace], $result);
    }

    public function testGetObjectFieldAces()
    {
        $ace = $this->createMock(EntryInterface::class);

        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $this->acl->expects($this->once())
            ->method('getObjectFieldAces')
            ->with($this->equalTo('SomeField'))
            ->willReturn([$ace]);
        $result = $obj->getObjectFieldAces('SomeField');

        $this->assertEquals([$ace], $result);
    }

    public function testGetObjectIdentity()
    {
        $id = new ObjectIdentity('1', 'SomeType');
        $this->acl->expects($this->once())
            ->method('getObjectAces')
            ->willReturn(['test']);
        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $this->acl->expects($this->once())
            ->method('getObjectIdentity')
            ->willReturn($id);
        $result = $obj->getObjectIdentity();

        $this->assertSame($id, $result);
    }

    public function testGetParentAcl()
    {
        $parentAcl = $this->createMock(Acl::class);

        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $this->acl->expects($this->once())
            ->method('getParentAcl')
            ->willReturn($parentAcl);
        $result = $obj->getParentAcl();

        $this->assertSame($parentAcl, $result);
    }

    public function testIsEntriesInheriting()
    {
        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $this->acl->expects($this->once())
            ->method('isEntriesInheriting')
            ->willReturn(true);
        $result = $obj->isEntriesInheriting();

        $this->assertTrue($result);
    }

    public function testIsSidLoaded()
    {
        $sid = new RoleSecurityIdentity('sid1');

        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $this->acl->expects($this->once())
            ->method('isSidLoaded')
            ->with($this->identicalTo($sid))
            ->willReturn(true);
        $result = $obj->isSidLoaded($sid);

        $this->assertTrue($result);
    }

    public function testIsGranted()
    {
        $sid = new RoleSecurityIdentity('sid1');

        $strategy = $this->createMock(PermissionGrantingStrategyInterface::class);

        $acl = new Acl(
            1,
            new ObjectIdentity('Test\Entity1', 'entity'),
            $strategy,
            [],
            false
        );

        $obj = new RootBasedAclWrapper(
            $acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $strategy->expects($this->once())
            ->method('isGranted')
            ->with(
                $this->identicalTo($obj),
                $this->equalTo([1]),
                $this->equalTo([$sid]),
                $this->equalTo(true)
            )
            ->willReturn(true);

        $result = $obj->isGranted([1], [$sid], true);

        $this->assertTrue($result);
    }

    public function testIsFieldGranted()
    {
        $sid = new RoleSecurityIdentity('sid1');

        $strategy = $this->createMock(PermissionGrantingStrategyInterface::class);

        $acl = new Acl(
            1,
            new ObjectIdentity('Test\Entity1', 'entity'),
            $strategy,
            [],
            false
        );

        $obj = new RootBasedAclWrapper(
            $acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $strategy->expects($this->once())
            ->method('isFieldGranted')
            ->with(
                $this->identicalTo($obj),
                $this->equalTo('SomeField'),
                $this->equalTo([1]),
                $this->equalTo([$sid]),
                $this->equalTo(true)
            )
            ->willReturn(true);

        $result = $obj->isFieldGranted('SomeField', [1], [$sid], true);

        $this->assertTrue($result);
    }

    public function testSerialize()
    {
        $this->expectException(\LogicException::class);
        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $obj->serialize();
    }

    public function testUnserialize()
    {
        $this->expectException(\LogicException::class);
        $obj = new RootBasedAclWrapper(
            $this->acl,
            new RootAclWrapper($this->rootAcl, new SecurityIdentityToStringConverter())
        );
        $obj->unserialize('');
    }
}
