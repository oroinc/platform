<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\FullAccessFieldRootAclBuilder;
use Oro\Bundle\SecurityBundle\Acl\Domain\RootAclWrapper;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\FieldMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Extension\NullAclExtension;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\FieldEntry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

class FullAccessFieldRootAclBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclExtensionSelector|\PHPUnit\Framework\MockObject\MockObject */
    private $extensionSelector;

    /** @var FullAccessFieldRootAclBuilder */
    private $fullAccessFieldRootAclBuilder;

    protected function setUp(): void
    {
        $this->extensionSelector = $this->createMock(AclExtensionSelector::class);

        $this->fullAccessFieldRootAclBuilder = new FullAccessFieldRootAclBuilder($this->extensionSelector);
    }

    public function testFillFieldRootAcesWithoutProperAclExtension(): void
    {
        $acl = new Acl(
            null,
            new ObjectIdentity('testExtension', '(root)'),
            $this->createMock(PermissionGrantingStrategyInterface::class),
            [],
            false
        );

        $this->extensionSelector->expects(self::once())
            ->method('selectByExtensionKey')
            ->with('testExtension')
            ->willReturn(null);

        $this->fullAccessFieldRootAclBuilder->fillFieldRootAces($acl, []);

        self::assertEmpty($acl->getClassFieldAces(RootAclWrapper::ROOT_FIELD_NAME));
    }

    public function testFillFieldRootAcesWithoutProperFieldAclExtension(): void
    {
        $acl = new Acl(
            null,
            new ObjectIdentity('testExtension', '(root)'),
            $this->createMock(PermissionGrantingStrategyInterface::class),
            [],
            false
        );

        $aclExtension = new NullAclExtension();

        $this->extensionSelector->expects(self::once())
            ->method('selectByExtensionKey')
            ->with('testExtension')
            ->willReturn($aclExtension);

        $this->fullAccessFieldRootAclBuilder->fillFieldRootAces($acl, []);

        self::assertEmpty($acl->getClassFieldAces(RootAclWrapper::ROOT_FIELD_NAME));
    }

    public function testFillFieldRootAcesWithEmptySids(): void
    {
        $acl = new Acl(
            null,
            new ObjectIdentity('testExtension', '(root)'),
            $this->createMock(PermissionGrantingStrategyInterface::class),
            [],
            false
        );

        $aclExtension = $this->createMock(AclExtensionInterface::class);
        $fieldAclExtension = $this->createMock(FieldAclExtension::class);
        $maskBuilder = new FieldMaskBuilder();

        $this->extensionSelector->expects(self::once())
            ->method('selectByExtensionKey')
            ->with('testExtension')
            ->willReturn($aclExtension);
        $aclExtension->expects(self::once())
            ->method('getFieldExtension')
            ->willReturn($fieldAclExtension);
        $fieldAclExtension->expects(self::once())
            ->method('getAllMaskBuilders')
            ->willReturn([$maskBuilder]);

        $this->fullAccessFieldRootAclBuilder->fillFieldRootAces($acl, []);

        self::assertEmpty($acl->getClassFieldAces(RootAclWrapper::ROOT_FIELD_NAME));
    }

    public function testFillFieldRootAcesWithSidsWithoutRoleSids(): void
    {
        $acl = new Acl(
            null,
            new ObjectIdentity('testExtension', '(root)'),
            $this->createMock(PermissionGrantingStrategyInterface::class),
            [],
            false
        );

        $sid1 = new UserSecurityIdentity('test', \stdClass::class);

        $aclExtension = $this->createMock(AclExtensionInterface::class);
        $fieldAclExtension = $this->createMock(FieldAclExtension::class);
        $maskBuilder = new FieldMaskBuilder();

        $this->extensionSelector->expects(self::once())
            ->method('selectByExtensionKey')
            ->with('testExtension')
            ->willReturn($aclExtension);
        $aclExtension->expects(self::once())
            ->method('getFieldExtension')
            ->willReturn($fieldAclExtension);
        $fieldAclExtension->expects(self::once())
            ->method('getAllMaskBuilders')
            ->willReturn([$maskBuilder]);

        $this->fullAccessFieldRootAclBuilder->fillFieldRootAces($acl, [$sid1]);

        self::assertEmpty($acl->getClassFieldAces(RootAclWrapper::ROOT_FIELD_NAME));
    }

    public function testFillFieldRootAces(): void
    {
        $acl = new Acl(
            null,
            new ObjectIdentity('testExtension', '(root)'),
            $this->createMock(PermissionGrantingStrategyInterface::class),
            [],
            false
        );

        $sid1 = new UserSecurityIdentity('test', \stdClass::class);
        $sid2 = new RoleSecurityIdentity('role1');
        $sid3 = new RoleSecurityIdentity('role2');

        $aclExtension = $this->createMock(AclExtensionInterface::class);
        $fieldAclExtension = $this->createMock(FieldAclExtension::class);
        $maskBuilder = new FieldMaskBuilder();

        $this->extensionSelector->expects(self::once())
            ->method('selectByExtensionKey')
            ->with('testExtension')
            ->willReturn($aclExtension);
        $aclExtension->expects(self::once())
            ->method('getFieldExtension')
            ->willReturn($fieldAclExtension);
        $fieldAclExtension->expects(self::once())
            ->method('getAllMaskBuilders')
            ->willReturn([$maskBuilder]);

        $this->fullAccessFieldRootAclBuilder->fillFieldRootAces($acl, [$sid1, $sid2, $sid3]);

        $aces = $acl->getClassFieldAces(RootAclWrapper::ROOT_FIELD_NAME);
        self::assertCount(2, $aces);

        /** @var FieldEntry $ace1 */
        $ace1 = $aces[0];
        self::assertNull($ace1->getId());
        self::assertEquals(RootAclWrapper::ROOT_FIELD_NAME, $ace1->getField());
        self::assertEquals($maskBuilder->getMaskForGroup('SYSTEM'), $ace1->getMask());
        self::assertEquals('all', $ace1->getStrategy());
        self::assertFalse($ace1->isAuditFailure());
        self::assertFalse($ace1->isAuditSuccess());
        self::assertTrue($ace1->isGranting());
        self::assertSame($sid2, $ace1->getSecurityIdentity());
        self::assertSame($acl, $ace1->getAcl());

        /** @var FieldEntry $ace2 */
        $ace2 = $aces[1];
        self::assertNull($ace2->getId());
        self::assertEquals(RootAclWrapper::ROOT_FIELD_NAME, $ace2->getField());
        self::assertEquals($maskBuilder->getMaskForGroup('SYSTEM'), $ace2->getMask());
        self::assertEquals('all', $ace2->getStrategy());
        self::assertFalse($ace2->isAuditFailure());
        self::assertFalse($ace2->isAuditSuccess());
        self::assertTrue($ace2->isGranting());
        self::assertSame($sid3, $ace2->getSecurityIdentity());
        self::assertSame($acl, $ace2->getAcl());
    }
}
