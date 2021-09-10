<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Extension\NullAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AceManipulationHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class AceManipulationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var AceManipulationHelper */
    private $manipulator;

    /** @var MutableAclInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $acl;

    protected function setUp(): void
    {
        $this->acl = $this->createMock(MutableAclInterface::class);
        $this->manipulator = new AceManipulationHelper();
    }

    /**
     * @dataProvider aceTypesProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetPermissionShouldCallUpdateAceForAce3(string $type, ?string $field)
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $replace = true;
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $aceSid1 = $this->createMock(SecurityIdentityInterface::class);
        $aceGranting1 = $granting;
        $aceMask1 = $mask;
        $aceStrategy1 = $strategy;

        $aceSid2 = $this->createMock(SecurityIdentityInterface::class);

        $aceSid3 = $this->createMock(SecurityIdentityInterface::class);
        $aceGranting3 = $granting;
        $aceMask3 = 789;
        $aceStrategy3 = $strategy;

        $ace1 = $this->getAce($aceSid1, $aceGranting1, $aceMask1, $aceStrategy1);
        $ace2 = $this->getAce($aceSid2);
        $ace3 = $this->getAce($aceSid3, $aceGranting3, $aceMask3, $aceStrategy3, 2, 0);

        $sid->expects($this->exactly(3))
            ->method('equals')
            ->withConsecutive(
                [$this->identicalTo($aceSid1)],
                [$this->identicalTo($aceSid2)],
                [$this->identicalTo($aceSid3)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                true
            );

        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'Aces')
                ->willReturn([$ace1, $ace2, $ace3]);
        } else {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'FieldAces')
                ->with($this->equalTo($field))
                ->willReturn([$ace1, $ace2, $ace3]);
        }

        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('update' . $type . 'Ace')
                ->with(
                    $this->equalTo(2),
                    $this->equalTo($mask),
                    $this->equalTo($strategy)
                );
        } else {
            $this->acl->expects($this->once())
                ->method('update' . $type . 'FieldAce')
                ->with(
                    $this->equalTo(2),
                    $this->equalTo($field),
                    $this->equalTo($mask),
                    $this->equalTo($strategy)
                );
        }
        $this->acl->expects($this->never())
            ->method('insert' . $type . 'Ace');
        $this->acl->expects($this->never())
            ->method('insert' . $type . 'FieldAce');

        $this->assertTrue(
            $this->manipulator->setPermission(
                $this->acl,
                new NullAclExtension(),
                $replace,
                $type,
                $field,
                $sid,
                $granting,
                $mask,
                $strategy
            )
        );
    }

    /**
     * @dataProvider aceTypesProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetPermissionShouldCallInsertAce(string $type, ?string $field)
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $replace = false;
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $aceSid1 = $this->createMock(SecurityIdentityInterface::class);

        $aceSid2 = $this->createMock(SecurityIdentityInterface::class);
        $aceGranting2 = $granting;
        $aceMask2 = $mask;
        $aceStrategy2 = 'all';

        $ace1 = $this->getAce($aceSid1);
        $ace2 = $this->getAce($aceSid2, $aceGranting2, $aceMask2, $aceStrategy2);

        $sid->expects($this->exactly(2))
            ->method('equals')
            ->withConsecutive(
                [$this->identicalTo($aceSid1)],
                [$this->identicalTo($aceSid2)]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'Aces')
                ->willReturn([$ace1, $ace2]);
        } else {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'FieldAces')
                ->with($this->equalTo($field))
                ->willReturn([$ace1, $ace2]);
        }

        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('insert' . $type . 'Ace')
                ->with(
                    $this->identicalTo($sid),
                    $this->equalTo($mask),
                    $this->equalTo(0),
                    $this->equalTo($granting),
                    $this->equalTo($strategy)
                );
        } else {
            $this->acl->expects($this->once())
                ->method('insert' . $type . 'FieldAce')
                ->with(
                    $this->equalTo($field),
                    $this->identicalTo($sid),
                    $this->equalTo($mask),
                    $this->equalTo(0),
                    $this->equalTo($granting),
                    $this->equalTo($strategy)
                );
        }
        $this->acl->expects($this->never())
            ->method('update' . $type . 'Ace');
        $this->acl->expects($this->never())
            ->method('update' . $type . 'FieldAce');

        $this->assertTrue(
            $this->manipulator->setPermission(
                $this->acl,
                new NullAclExtension(),
                $replace,
                $type,
                $field,
                $sid,
                $granting,
                $mask,
                $strategy
            )
        );
    }

    /**
     * @dataProvider aceTypesProvider
     */
    public function testDeletePermission(string $type, ?string $field)
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $granting = true;
        $mask = 123;
        $strategy = 'any';

        $aceSid1 = $this->createMock(SecurityIdentityInterface::class);
        $aceGranting1 = true;
        $aceMask1 = 123;
        $aceStrategy1 = 'equal';

        $aceSid2 = $this->createMock(SecurityIdentityInterface::class);

        $aceSid3 = $this->createMock(SecurityIdentityInterface::class);
        $aceGranting3 = $granting;
        $aceMask3 = $mask;
        $aceStrategy3 = $strategy;

        $ace1 = $this->getAce($aceSid1, $aceGranting1, $aceMask1, $aceStrategy1);
        $ace2 = $this->getAce($aceSid2);
        $ace3 = $this->getAce($aceSid3, $aceGranting3, $aceMask3, $aceStrategy3);

        $sid->expects($this->exactly(3))
            ->method('equals')
            ->withConsecutive(
                [$this->identicalTo($aceSid1)],
                [$this->identicalTo($aceSid2)],
                [$this->identicalTo($aceSid3)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                true
            );

        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'Aces')
                ->willReturn([$ace1, $ace2, $ace3]);
        } else {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'FieldAces')
                ->with($this->equalTo($field))
                ->willReturn([$ace1, $ace2, $ace3]);
        }
        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('delete' . $type . 'Ace')
                ->with(
                    $this->equalTo(2)
                );
        } else {
            $this->acl->expects($this->once())
                ->method('delete' . $type . 'FieldAce')
                ->with(
                    $this->equalTo(2),
                    $this->equalTo($field)
                );
        }

        $this->assertTrue(
            $this->manipulator->deletePermission($this->acl, $type, $field, $sid, $granting, $mask, $strategy)
        );
    }

    /**
     * @dataProvider aceTypesProvider
     */
    public function testDeleteAllPermissions(string $type, ?string $field)
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);

        $aceSid1 = $this->createMock(SecurityIdentityInterface::class);
        $aceSid2 = $this->createMock(SecurityIdentityInterface::class);
        $ace1 = $this->getAce($aceSid1);
        $ace2 = $this->getAce($aceSid2);

        $sid->expects($this->exactly(2))
            ->method('equals')
            ->withConsecutive(
                [$this->identicalTo($aceSid1)],
                [$this->identicalTo($aceSid2)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'Aces')
                ->willReturn([$ace1, $ace2]);
        } else {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'FieldAces')
                ->with($this->equalTo($field))
                ->willReturn([$ace1, $ace2]);
        }
        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('delete' . $type . 'Ace')
                ->with(
                    $this->equalTo(0)
                );
        } else {
            $this->acl->expects($this->once())
                ->method('delete' . $type . 'FieldAce')
                ->with(
                    $this->equalTo(0),
                    $this->equalTo($field)
                );
        }

        $this->assertTrue($this->manipulator->deleteAllPermissions($this->acl, $type, $field, $sid));
    }

    /**
     * @dataProvider aceTypesProvider
     */
    public function testGetAces(string $type, ?string $field)
    {
        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'Aces')
                ->willReturn([]);
        } else {
            $this->acl->expects($this->once())
                ->method('get' . $type . 'FieldAces')
                ->with($this->equalTo($field))
                ->willReturn([]);
        }

        $this->assertEquals(
            [],
            $this->manipulator->getAces($this->acl, $type, $field)
        );
    }

    /**
     * @dataProvider aceTypesProvider
     */
    public function testInsertAce(string $type, ?string $field)
    {
        $index = 1;
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $granting = true;
        $mask = 123;
        $strategy = 'any';
        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('insert' . $type . 'Ace')
                ->with(
                    $this->identicalTo($sid),
                    $this->equalTo($mask),
                    $this->equalTo($index),
                    $this->equalTo($granting),
                    $this->equalTo($strategy)
                );
        } else {
            $this->acl->expects($this->once())
                ->method('insert' . $type . 'FieldAce')
                ->with(
                    $this->equalTo($field),
                    $this->identicalTo($sid),
                    $this->equalTo($mask),
                    $this->equalTo($index),
                    $this->equalTo($granting),
                    $this->equalTo($strategy)
                );
        }

        $this->manipulator->insertAce($this->acl, $type, $field, $index, $sid, $granting, $mask, $strategy);
    }

    /**
     * @dataProvider aceTypesProvider
     */
    public function testUpdateAce(string $type, ?string $field)
    {
        $index = 1;
        $mask = 123;
        $strategy = 'any';
        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('update' . $type . 'Ace')
                ->with(
                    $this->equalTo($index),
                    $this->equalTo($mask),
                    $this->equalTo($strategy)
                );
        } else {
            $this->acl->expects($this->once())
                ->method('update' . $type . 'FieldAce')
                ->with(
                    $this->equalTo($index),
                    $this->equalTo($field),
                    $this->equalTo($mask),
                    $this->equalTo($strategy)
                );
        }

        $this->manipulator->updateAce($this->acl, $type, $field, $index, $mask, $strategy);
    }

    /**
     * @dataProvider aceTypesProvider
     */
    public function testDeleteAce(string $type, ?string $field)
    {
        $index = 1;
        if ($field === null) {
            $this->acl->expects($this->once())
                ->method('delete' . $type . 'Ace')
                ->with(
                    $this->equalTo($index)
                );
        } else {
            $this->acl->expects($this->once())
                ->method('delete' . $type . 'FieldAce')
                ->with(
                    $this->equalTo($index),
                    $this->equalTo($field)
                );
        }

        $this->manipulator->deleteAce($this->acl, $type, $field, $index);
    }

    public static function aceTypesProvider(): array
    {
        return [
            [AclManager::CLASS_ACE, null],
            [AclManager::OBJECT_ACE, null],
            [AclManager::CLASS_ACE, 'SomeField'],
            [AclManager::OBJECT_ACE, 'SomeField'],
        ];
    }

    private function getAce(
        SecurityIdentityInterface $sid,
        bool $granting = null,
        int $mask = null,
        string $strategy = null,
        int $getMaskCallCount = 1,
        int $getStrategyCallCount = 1
    ): EntryInterface {
        $ace = $this->createMock(EntryInterface::class);
        $ace->expects($this->once())
            ->method('getSecurityIdentity')
            ->willReturn($sid);
        if ($granting !== null) {
            $ace->expects($this->once())
                ->method('isGranting')
                ->willReturn($granting);
        }
        if ($mask !== null) {
            $ace->expects($this->exactly($getMaskCallCount))
                ->method('getMask')
                ->willReturn($mask);
        }
        if ($strategy !== null) {
            $ace->expects($this->exactly($getStrategyCallCount))
                ->method('getStrategy')
                ->willReturn($strategy);
        }

        return $ace;
    }
}
