<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AceManipulationHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\ClassSecurityMetadataStub;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity as OID;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclPrivilegeRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var AclExtensionSelector|\PHPUnit\Framework\MockObject\MockObject */
    private $extensionSelector;

    /** @var AclExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $extension;

    /** @var AceManipulationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aceProvider;

    private array $expectationsForSetPermission;
    private array $triggeredExpectationsForSetPermission;

    /** @var AclPrivilegeRepository */
    private $repository;

    protected function setUp(): void
    {
        $this->extension = $this->createMock(AclExtensionInterface::class);
        $this->extension->expects(self::any())
            ->method('getObjectIdentity')
            ->willReturnCallback(fn ($object) => new ObjectIdentity(
                substr($object, 0, strpos($object, ':')),
                substr($object, strpos($object, ':') + 1)
            ));
        $this->extension->expects(self::any())
            ->method('getMaskBuilder')
            ->willReturn(new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT']));
        $this->extension->expects(self::any())
            ->method('getAllMaskBuilders')
            ->willReturn([new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT'])]);

        $this->extensionSelector = $this->createMock(AclExtensionSelector::class);
        $this->extensionSelector->expects(self::any())
            ->method('select')
            ->willReturn($this->extension);
        $this->extensionSelector->expects(self::any())
            ->method('selectByExtensionKey')
            ->willReturn($this->extension);

        $this->aceProvider = $this->createMock(AceManipulationHelper::class);

        $this->manager = $this->createMock(AclManager::class);
        $this->manager->expects(self::any())
            ->method('getExtensionSelector')
            ->willReturn($this->extensionSelector);
        $this->manager->expects(self::any())
            ->method('getAllExtensions')
            ->willReturn([$this->extension]);
        $this->manager->expects(self::any())
            ->method('getAceProvider')
            ->willReturn($this->aceProvider);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label, $parameters, $domain) {
                $result = 'translated: ' . $label;
                if (!empty($parameters)) {
                    foreach ($parameters as $key => $val) {
                        $result .= ' ' . $key . ': (' . $val . ')';
                    }
                }
                if (!empty($domain)) {
                    $result .= ' [domain: ' . $domain . ']';
                }

                return $result;
            });

        $this->repository = new AclPrivilegeRepository($this->manager, $this->translator);
    }

    public function testGetPermissionNames(): void
    {
        $extensionKey = 'test';
        $permissions = ['VIEW', 'EDIT'];

        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->willReturn($permissions);

        self::assertEquals(
            $permissions,
            $this->repository->getPermissionNames($extensionKey)
        );
    }

    public function testGetPermissionNamesForSeveralAclExtensions()
    {
        $extensionKey1 = 'test1';
        $permissions1 = ['VIEW', 'EDIT'];

        $extensionKey2 = 'test2';
        $permissions2 = ['VIEW', 'CREATE'];

        $this->extension->expects(self::exactly(2))
            ->method('getPermissions')
            ->willReturnOnConsecutiveCalls($permissions1, $permissions2);

        self::assertEquals(
            ['VIEW', 'EDIT', 'CREATE'],
            $this->repository->getPermissionNames([$extensionKey1, $extensionKey2])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testGetPrivileges(): void
    {
        $sid = $this->createMock(SecurityIdentityInterface::class);
        $sid->expects(self::any())
            ->method('equals')
            ->willReturn(true);

        $extensionKey = 'test';
        $classes = [
            'Acme\Class1',
            'Acme\Class2',
        ];
        $class1 = new ClassSecurityMetadataStub($classes[0], 'SomeGroup', 'Class 1', 'Desc 1', 'Category 1');
        $class2 = new ClassSecurityMetadataStub($classes[1], 'SomeGroup', 'Class 2', 'Desc 2', 'Category 2');

        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $rootAcl = $this->createMock(AclInterface::class);

        $oid1 = new ObjectIdentity($extensionKey, $classes[0]);
        $oid1Acl = $this->createMock(AclInterface::class);
        $oid2 = new ObjectIdentity($extensionKey, $classes[1]);

        $oidsWithRoot = [$rootOid, $oid2, $oid1];

        $aclsSrc = [
            ['oid' => $rootOid, 'acl' => $rootAcl],
            ['oid' => $oid1, 'acl' => $oid1Acl],
            ['oid' => $oid2, 'acl' => null],
        ];

        $allowedPermissions = [];
        $allowedPermissions[(string)$rootOid] = ['VIEW', 'CREATE', 'EDIT'];
        $allowedPermissions[(string)$oid1] = ['VIEW', 'CREATE', 'EDIT'];
        $allowedPermissions[(string)$oid2] = ['VIEW', 'CREATE'];

        $rootAce = $this->getAce('root', $sid);
        $rootAcl->expects(self::any())
            ->method('getObjectAces')
            ->willReturn([$rootAce]);
        $rootAcl->expects(self::never())
            ->method('getClassAces');

        $oid1Ace = $this->getAce('oid1', $sid);
        $oid1Acl->expects(self::any())
            ->method('getClassAces')
            ->willReturn([$oid1Ace]);
        $oid1Acl->expects(self::once())
            ->method('getObjectAces')
            ->willReturn([]);

        $this->extension->expects(self::once())
            ->method('getExtensionKey')
            ->willReturn($extensionKey);
        $this->extension->expects(self::once())
            ->method('getClasses')
            ->willReturn([$class2, $class1]);
        $this->extension->expects(self::any())
            ->method('getAllowedPermissions')
            ->willReturnCallback(static fn ($oid) => $allowedPermissions[(string)$oid]);
        $this->extension->expects(self::any())
            ->method('adaptRootMask')
            ->willReturnCallback(function ($mask, $object) {
                if ($mask === 'root' && $object === 'test:Acme\Class2') {
                    return 'adaptedRoot';
                }

                return $mask;
            });
        $this->extension->expects(self::any())
            ->method('getPermissions')
            ->willReturn(['VIEW', 'CREATE', 'EDIT']);
        $this->extension->expects(self::any())
            ->method('getAccessLevel')
            ->willReturnCallback(function ($mask, $permission) {
                switch ($permission) {
                    case 'VIEW':
                        if ($mask === 'root') {
                            return AccessLevel::GLOBAL_LEVEL;
                        }
                        if ($mask === 'oid1') {
                            return AccessLevel::BASIC_LEVEL;
                        }
                        break;
                    case 'CREATE':
                        if ($mask === 'root') {
                            return AccessLevel::DEEP_LEVEL;
                        }
                        if ($mask === 'oid1') {
                            return AccessLevel::BASIC_LEVEL;
                        }
                        break;
                    case 'EDIT':
                        if ($mask === 'root') {
                            return AccessLevel::LOCAL_LEVEL;
                        }
                        if ($mask === 'oid1') {
                            return AccessLevel::NONE_LEVEL;
                        }
                        break;
                }
                if ($mask === 'adaptedRoot') {
                    return AccessLevel::SYSTEM_LEVEL;
                }

                return AccessLevel::NONE_LEVEL;
            });

        $this->manager->expects(self::once())
            ->method('getRootOid')
            ->with(self::equalTo($extensionKey))
            ->willReturn($rootOid);

        $this->manager->expects(self::once())
            ->method('findAcls')
            ->with(self::identicalTo($sid), self::equalTo($oidsWithRoot))
            ->willReturnCallback(fn () => $this->getAcls($aclsSrc));

        $this->aceProvider->expects(self::any())
            ->method('getAces')
            ->willReturnCallback(function ($acl, $type, $field) use (&$rootAcl, &$oid1Acl) {
                if ($acl === $oid1Acl) {
                    $a = $oid1Acl;
                } else {
                    $a = $rootAcl;
                }

                return $a->{"get{$type}Aces"}();
            });

        $result = $this->repository->getPrivileges($sid);

        self::assertCount(count($classes), $result);
        self::assertEquals($extensionKey . ':' . $class1->getClassName(), $result[0]->getIdentity()->getId());
        self::assertEquals($class1->getLabel(), $result[0]->getIdentity()->getName());
        self::assertEquals($class1->getGroup(), $result[0]->getGroup());
        self::assertEquals($class1->getDescription(), $result[0]->getDescription());
        self::assertEquals($class1->getCategory(), $result[0]->getCategory());
        self::assertEquals($extensionKey, $result[0]->getExtensionKey());
        self::assertEquals($extensionKey . ':' . $class2->getClassName(), $result[1]->getIdentity()->getId());
        self::assertEquals($class2->getLabel(), $result[1]->getIdentity()->getName());
        self::assertEquals($class2->getGroup(), $result[1]->getGroup());
        self::assertEquals($class2->getDescription(), $result[1]->getDescription());
        self::assertEquals($class2->getCategory(), $result[1]->getCategory());
        self::assertEquals($extensionKey, $result[1]->getExtensionKey());

        self::assertEquals(3, $result[0]->getPermissionCount());
        self::assertEquals(2, $result[1]->getPermissionCount());

        $p = $result[0]->getPermissions();
        self::assertEquals(AccessLevel::BASIC_LEVEL, $p['VIEW']->getAccessLevel());
        self::assertEquals(AccessLevel::BASIC_LEVEL, $p['CREATE']->getAccessLevel());
        self::assertEquals(AccessLevel::NONE_LEVEL, $p['EDIT']->getAccessLevel());

        $p = $result[1]->getPermissions();
        self::assertEquals(AccessLevel::SYSTEM_LEVEL, $p['VIEW']->getAccessLevel());
        self::assertEquals(AccessLevel::SYSTEM_LEVEL, $p['CREATE']->getAccessLevel());
        self::assertFalse($p->containsKey('EDIT'));
    }

    /**
     * @dataProvider getPrivilegesWithFieldsDataProvider
     *
     * @param FieldSecurityMetadata[] $fields
     * @param ArrayCollection $expectation
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetPrivilegesWithFields(array $fields, ArrayCollection $expectation): void
    {
        $sid = new RoleSecurityIdentity('ROLE_ADMINISTRATOR');
        $extensionKey = 'test';

        $class1 = new ClassSecurityMetadataStub('Acme\Class1', 'SomeGroup', 'Class 1', 'Desc 1', 'Category 1', $fields);

        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $rootAcl = $this->createMock(AclInterface::class);

        $oid1 = new ObjectIdentity($extensionKey, $class1->getClassName());
        $oid1Acl = $this->createMock(AclInterface::class);
        $oidsWithRoot = [$rootOid, $oid1];

        $aclsSrc = [
            ['oid' => $rootOid, 'acl' => $rootAcl],
            ['oid' => $oid1, 'acl' => $oid1Acl],
        ];

        $allowedPermissions = [
            (string)$rootOid => ['VIEW', 'CREATE', 'EDIT'],
            (string)$oid1 => ['VIEW', 'CREATE', 'EDIT'],
        ];

        $rootAcl->expects(self::never())
            ->method('getClassAces');

        $oid1Ace = $this->getAce('oid1', $sid);
        $oid1Acl->expects(self::any())
            ->method('getClassAces')
            ->willReturn([$oid1Ace]);
        $oid1Acl->expects(self::any())
            ->method('getObjectAces')
            ->willReturn([]);

        $this->extension->expects(self::exactly(2))
            ->method('getExtensionKey')
            ->willReturn($extensionKey);
        $this->extension->expects(self::once())
            ->method('getClasses')
            ->willReturn([$class1]);
        $this->extension->expects(self::any())
            ->method('getAllowedPermissions')
            ->willReturnCallback(static fn ($oid) => $allowedPermissions[(string)$oid]);
        $this->extension->expects(self::any())
            ->method('getPermissions')
            ->willReturn(['VIEW', 'CREATE', 'EDIT']);
        $this->extension->expects(self::any())
            ->method('getAccessLevel')
            ->willReturnCallback(function ($mask, $permission) {
                switch ($permission) {
                    case 'VIEW':
                    case 'CREATE':
                        return AccessLevel::BASIC_LEVEL;
                    case 'EDIT':
                        return AccessLevel::NONE_LEVEL;
                    default:
                        return $mask === 'adaptedRoot'
                            ? AccessLevel::SYSTEM_LEVEL
                            : AccessLevel::NONE_LEVEL;
                }
            });
        $this->extension->expects(self::any())
            ->method('getFieldExtension')
            ->willReturnSelf();

        $this->manager->expects(self::once())
            ->method('getRootOid')
            ->with(self::equalTo($extensionKey))
            ->willReturn($rootOid);
        $this->manager->expects(self::exactly(2))
            ->method('findAcls')
            ->withConsecutive(
                [self::identicalTo($sid), self::equalTo($oidsWithRoot)],
                [self::identicalTo($sid), self::equalTo([new OID($extensionKey, $class1->getClassName())])]
            )
            ->willReturnCallback(fn () => $this->getAcls($aclsSrc));

        $this->aceProvider->expects(self::any())
            ->method('getAces')
            ->willReturnCallback(function ($acl, $type, $field) use (&$rootAcl, &$oid1Acl) {
                return $acl === $oid1Acl
                    ? $oid1Acl->{"get{$type}Aces"}()
                    : $rootAcl->{"get{$type}Aces"}();
            });

        $result = $this->repository->getPrivileges($sid);
        self::assertCount(1, $result);
        self::assertEquals(
            $extensionKey . ':' . $class1->getClassName(),
            $result[0]->getIdentity()->getId()
        );
        self::assertEquals($expectation, $result[0]->getFields());
    }

    public function getPrivilegesWithFieldsDataProvider(): array
    {
        $fieldPrivilege1 = new AclPrivilege();
        $fieldPrivilege1->setIdentity(new AclPrivilegeIdentity('test:Acme\Class1::field1', 'field1Label'));
        $fieldPrivilege1->addPermission(new AclPermission('VIEW', AccessLevel::BASIC_LEVEL));
        $fieldPrivilege1->addPermission(new AclPermission('CREATE', AccessLevel::BASIC_LEVEL));
        $fieldPrivilege1->addPermission(new AclPermission('EDIT', AccessLevel::NONE_LEVEL));

        $fieldPrivilege2 = new AclPrivilege();
        $fieldPrivilege2->setIdentity(new AclPrivilegeIdentity('test:Acme\Class1::field2', 'field2Label'));
        $fieldPrivilege2->addPermission(new AclPermission('VIEW', AccessLevel::BASIC_LEVEL));
        $fieldPrivilege2->addPermission(new AclPermission('CREATE', AccessLevel::BASIC_LEVEL));
        $fieldPrivilege2->addPermission(new AclPermission('EDIT', AccessLevel::NONE_LEVEL));

        return [
            'with two regular fields' => [
                'fields' => [
                    new FieldSecurityMetadata('field1', 'field1Label'),
                    new FieldSecurityMetadata('field2', 'field2Label'),
                ],
                'expectation' => new ArrayCollection([$fieldPrivilege1, $fieldPrivilege2]),
            ],
            'with two regular fields plus one hidden' => [
                'fields' => [
                    new FieldSecurityMetadata('field0', 'field0Label', [], null, null, true),
                    new FieldSecurityMetadata('field1', 'field1Label'),
                    new FieldSecurityMetadata('field2', 'field2Label'),
                ],
                'expectation' => new ArrayCollection([$fieldPrivilege1, $fieldPrivilege2])
            ],
            'with two regular fields, one with alias plus one hidden' => [
                'fields' => [
                    new FieldSecurityMetadata('field0', 'field0Label', [], null, null, true),
                    new FieldSecurityMetadata('field1', 'field1Label', [], null, 'field2', false),
                    new FieldSecurityMetadata('field2', 'field2Label'),
                ],
                'expectation' => new ArrayCollection([$fieldPrivilege1, $fieldPrivilege2]),
            ],
            'all fields are hidden' => [
                'fields' => [
                    new FieldSecurityMetadata('field1', 'field1Label', [], null, null, true),
                    new FieldSecurityMetadata('field2', 'field2Label', [], null, null, true),
                ],
                'expectation' => new ArrayCollection(),
            ],
        ];
    }

    /**
     * Checks that if a permission is not set in AclPrivilege of a field then it should be added with the max allowed
     * access level.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetPrivilegesWithFieldAndMissingPermission(): void
    {
        $sid = new RoleSecurityIdentity('ROLE_ADMINISTRATOR');
        $extensionKey = 'test';

        $field = new FieldSecurityMetadata('field_with_missing_permission', 'fieldLabel');
        $class1 = new ClassSecurityMetadataStub('Acme\Class1', 'SomeGroup', 'Class', 'Desc', 'Category', [$field]);

        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $rootAcl = $this->createMock(AclInterface::class);

        $oid1 = new ObjectIdentity($extensionKey, $class1->getClassName());
        $oid1Acl = $this->createMock(AclInterface::class);
        $oidsWithRoot = [$rootOid, $oid1];

        $aclsSrc = [
            ['oid' => $rootOid, 'acl' => $rootAcl],
            ['oid' => $oid1, 'acl' => $oid1Acl],
        ];

        $allowedPermissions = [
            (string)$rootOid => ['VIEW', 'CREATE', 'EDIT'],
            (string)$oid1 => ['VIEW', 'CREATE', 'EDIT'],
            ($oid1 . $field->getFieldName()) => ['VIEW', 'EDIT', 'CUSTOM'],
        ];

        $rootAcl->expects(self::never())
            ->method('getClassAces');

        $oid1Ace = $this->getAce('oid1', $sid);
        $oid1Acl->expects(self::any())
            ->method('getClassAces')
            ->willReturn([$oid1Ace]);
        $oid1Acl->expects(self::any())
            ->method('getObjectAces')
            ->willReturn([]);

        $this->extension->expects(self::exactly(2))
            ->method('getExtensionKey')
            ->willReturn($extensionKey);
        $this->extension->expects(self::once())
            ->method('getClasses')
            ->willReturn([$class1]);
        $this->extension->expects(self::any())
            ->method('getAllowedPermissions')
            ->willReturnCallback(static fn ($oid, $field) => $allowedPermissions[$oid . $field]);
        $this->extension->expects(self::any())
            ->method('getPermissions')
            ->willReturn(['VIEW', 'CREATE', 'EDIT']);
        $this->extension->expects(self::any())
            ->method('getAccessLevel')
            ->willReturnCallback(function ($mask, $permission) {
                switch ($permission) {
                    case 'VIEW':
                    case 'CREATE':
                        return AccessLevel::BASIC_LEVEL;
                    case 'EDIT':
                        return AccessLevel::NONE_LEVEL;
                    default:
                        return $mask === 'adaptedRoot'
                            ? AccessLevel::SYSTEM_LEVEL
                            : AccessLevel::NONE_LEVEL;
                }
            });
        $fieldOid = ObjectIdentityHelper::encodeIdentityString(
            $oid1->getIdentifier(),
            ObjectIdentityHelper::encodeEntityFieldInfo($oid1->getType(), $field->getFieldName())
        );
        $this->extension->expects(self::any())
            ->method('getAccessLevelNames')
            ->with($fieldOid, 'CUSTOM')
            ->willReturn(
                [AccessLevel::NONE_LEVEL => AccessLevel::NONE_LEVEL_NAME, AccessLevel::BASIC_LEVEL => 'BASIC']
            );
        $this->extension->expects(self::any())
            ->method('getFieldExtension')
            ->willReturnSelf();

        $this->manager->expects(self::once())
            ->method('getRootOid')
            ->with(self::equalTo($extensionKey))
            ->willReturn($rootOid);
        $this->manager->expects(self::exactly(2))
            ->method('findAcls')
            ->withConsecutive(
                [self::identicalTo($sid), self::equalTo($oidsWithRoot)],
                [self::identicalTo($sid), self::equalTo([new OID($extensionKey, $class1->getClassName())])]
            )
            ->willReturnCallback(fn () => $this->getAcls($aclsSrc));

        $this->aceProvider->expects(self::any())
            ->method('getAces')
            ->willReturnCallback(
                static function ($acl, $type, $field) use (&$rootAcl, &$oid1Acl) {
                    return $acl === $oid1Acl
                        ? $oid1Acl->{"get{$type}Aces"}()
                        : $rootAcl->{"get{$type}Aces"}();
                }
            );

        $result = $this->repository->getPrivileges($sid);
        self::assertCount(1, $result);
        self::assertEquals(
            $extensionKey . ':' . $class1->getClassName(),
            $result[0]->getIdentity()->getId()
        );
        $fieldPrivilege1 = new AclPrivilege();
        $fieldPrivilege1->setIdentity(
            new AclPrivilegeIdentity(
                'test:' . $class1->getClassName() . '::' . $field->getFieldName(),
                $field->getLabel()
            )
        );
        $fieldPrivilege1->addPermission(new AclPermission('VIEW', AccessLevel::BASIC_LEVEL));
        $fieldPrivilege1->addPermission(new AclPermission('EDIT', AccessLevel::NONE_LEVEL));
        $fieldPrivilege1->addPermission(new AclPermission('CUSTOM', AccessLevel::BASIC_LEVEL));
        self::assertEquals(new ArrayCollection([$fieldPrivilege1]), $result[0]->getFields());
    }

    private function initSavePrivileges($extensionKey, $rootOid): void
    {
        $this->extension->expects(self::any())
            ->method('getExtensionKey')
            ->willReturn($extensionKey);
        $this->extension->expects(self::any())
            ->method('getPermissions')
            ->willReturn(['VIEW', 'CREATE', 'EDIT']);
        $this->extension->expects(self::any())
            ->method('adaptRootMask')
            ->willReturnCallback(static fn ($mask, $object) => $mask);

        $this->manager->expects(self::any())
            ->method('getRootOid')
            ->with(self::equalTo($extensionKey))
            ->willReturn($rootOid);

        $this->manager->expects(self::once())
            ->method('flush');
    }

    private function validateExpectationsForSetPermission(): void
    {
        foreach ($this->expectationsForSetPermission as $expectedOid => $expectedMasks) {
            if (!isset($this->triggeredExpectationsForSetPermission[$expectedOid])) {
                throw new \RuntimeException(sprintf('Expected call of "setPermission" for %s.', $expectedOid));
            }
        }
    }

    private function setExpectationsForSetPermission($sid, array $expectations): void
    {
        $this->expectationsForSetPermission = $expectations;
        $this->triggeredExpectationsForSetPermission = [];
        $triggeredExpectationsForSetPermission = &$this->triggeredExpectationsForSetPermission;
        $this->manager->expects(self::any())
            ->method('setPermission')
            ->with(self::identicalTo($sid))
            ->willReturnCallback(
                function (
                    $sid,
                    $oid,
                    $mask
                ) use (
                    &$expectations,
                    &$triggeredExpectationsForSetPermission
                ) {
                    /** @var ObjectIdentity $oid */
                    $expectedMask = null;

                    foreach ($expectations as $expectedOid => $expectedMasks) {
                        if ($expectedOid === $oid->getIdentifier() . ':' . $oid->getType()) {
                            $expectedMask = $this->getMask($expectedMasks);
                            $triggeredExpectationsForSetPermission[$expectedOid] =
                                isset($triggeredExpectationsForSetPermission[$expectedOid])
                                    ? $triggeredExpectationsForSetPermission[$expectedOid] + 1
                                    : 0;
                            break;
                        }
                    }

                    if ($expectedMask !== null) {
                        if ($expectedMask !== $mask) {
                            throw new \RuntimeException(
                                sprintf(
                                    'Call "setPermission" with invalid mask for %s. Expected: %s. Actual: %s.',
                                    $oid,
                                    EntityMaskBuilder::getPatternFor($expectedMask),
                                    EntityMaskBuilder::getPatternFor($mask)
                                )
                            );
                        }
                    } else {
                        throw new \RuntimeException(sprintf('Unexpected call of "setPermission" for %s.', $oid));
                    }
                }
            );
    }

    private array $expectationsForDeletePermission;
    private array $triggeredExpectationsForDeletePermission;

    private function validateExpectationsForDeletePermission(): void
    {
        foreach ($this->expectationsForDeletePermission as $expectedOid => $expectedMasks) {
            if (!isset($this->triggeredExpectationsForDeletePermission[$expectedOid])) {
                throw new \RuntimeException(sprintf('Expected call of "deletePermission" for %s.', $expectedOid));
            }
        }
    }

    private function setExpectationsForDeletePermission($sid, array $expectations): void
    {
        $this->expectationsForDeletePermission = $expectations;
        $this->triggeredExpectationsForDeletePermission = [];
        $triggeredExpectationsForDeletePermission = &$this->triggeredExpectationsForDeletePermission;
        $this->manager->expects(self::any())
            ->method('deletePermission')
            ->with(self::identicalTo($sid))
            ->willReturnCallback(
                function (
                    $sid,
                    $oid,
                    $mask
                ) use (
                    &$expectations,
                    &$triggeredExpectationsForDeletePermission
                ) {
                    /** @var ObjectIdentity $oid */
                    $expectedMask = null;

                    foreach ($expectations as $expectedOid => $expectedMasks) {
                        if ($expectedOid === $oid->getIdentifier() . ':' . $oid->getType()) {
                            $expectedMask = $this->getMask($expectedMasks);
                            $triggeredExpectationsForDeletePermission[$expectedOid] =
                                isset($triggeredExpectationsForDeletePermission[$expectedOid])
                                    ? $triggeredExpectationsForDeletePermission[$expectedOid] + 1
                                    : 0;
                            break;
                        }
                    }

                    if ($expectedMask !== null) {
                        if ($expectedMask !== $mask) {
                            throw new \RuntimeException(
                                sprintf(
                                    'Call "deletePermission" with invalid mask for %s. Expected: %s. Actual: %s.',
                                    $oid,
                                    EntityMaskBuilder::getPatternFor($expectedMask),
                                    EntityMaskBuilder::getPatternFor($mask)
                                )
                            );
                        }
                    } else {
                        throw new \RuntimeException(sprintf('Unexpected call of "deletePermission" for %s.', $oid));
                    }
                }
            );
    }

    private array $expectationsForGetAces;
    private array $triggeredExpectationsForGetAces;

    private function validateExpectationsForGetAces(): void
    {
        foreach ($this->expectationsForGetAces as $expectedOid => $expectedMasks) {
            if (!isset($this->triggeredExpectationsForGetAces[$expectedOid])) {
                throw new \RuntimeException(sprintf('Expected call of "getAces" for %s.', $expectedOid));
            }
        }
    }

    private function setExpectationsForGetAces(array $expectations): void
    {
        $this->expectationsForGetAces = $expectations;
        $this->triggeredExpectationsForGetAces = [];
        $triggeredExpectationsForGetAces = &$this->triggeredExpectationsForGetAces;
        $this->manager->expects(self::any())
            ->method('getAces')
            ->willReturnCallback(
                function ($sid, $oid) use (&$expectations, &$triggeredExpectationsForGetAces) {
                    /** @var ObjectIdentity $oid */
                    foreach ($expectations as $expectedOid => $expectedAces) {
                        if ($expectedOid === $oid->getIdentifier() . ':' . $oid->getType()) {
                            $triggeredExpectationsForGetAces[$expectedOid] =
                                isset($triggeredExpectationsForGetAces[$expectedOid])
                                    ? $triggeredExpectationsForGetAces[$expectedOid] + 1
                                    : 0;

                            return $expectedAces;
                        }
                    }

                    return [];
                }
            );
    }

    public function testSavePrivilegesForNewRoleWithoutRoot(): void
    {
        $extensionKey = 'test';
        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);

        $privileges = new ArrayCollection();
        $privileges[] = self::getPrivilege(
            'test:Acme\Class1',
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );

        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->initSavePrivileges($extensionKey, $rootOid);

        $this->setExpectationsForGetAces([]);

        $this->setExpectationsForSetPermission(
            $sid,
            [
                'test:(root)'      => [],
                'test:Acme\Class1' => ['VIEW_SYSTEM', 'CREATE_BASIC'],
            ]
        );

        $this->repository->savePrivileges($sid, $privileges);

        $this->validateExpectationsForGetAces();
        $this->validateExpectationsForSetPermission();
    }

    public function testSavePrivilegesForNewRoleWithRoot(): void
    {
        $extensionKey = 'test';
        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);

        $privileges = new ArrayCollection();
        $privileges[] = self::getPrivilege(
            'test:(root)',
            [
                'VIEW' => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
            ]
        );
        $privileges[] = self::getPrivilege(
            'test:Acme\Class1',
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );
        $privileges[] = self::getPrivilege(
            'test:Acme\Class2',
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );

        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->initSavePrivileges($extensionKey, $rootOid);

        $this->setExpectationsForGetAces([]);

        $this->setExpectationsForSetPermission(
            $sid,
            [
                'test:(root)'      => ['VIEW_SYSTEM', 'CREATE_BASIC'],
                'test:Acme\Class2' => ['VIEW_SYSTEM', 'CREATE_SYSTEM'],
            ]
        );

        $this->repository->savePrivileges($sid, $privileges);

        $this->validateExpectationsForGetAces();
        $this->validateExpectationsForSetPermission();
    }

    public function testSavePrivilegesForExistingRole(): void
    {
        $extensionKey = 'test';
        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);

        $class3Ace = $this->getAce(self::getMask(['VIEW_BASIC', 'CREATE_BASIC']));

        $privileges = new ArrayCollection();
        $privileges[] = self::getPrivilege(
            'test:(root)',
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );
        $privileges[] = self::getPrivilege(
            'test:Acme\Class1', // no changes because permissions = root
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );
        $privileges[] = self::getPrivilege(
            'test:Acme\Class2', // new
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );
        $privileges[] = self::getPrivilege(
            'test:Acme\Class3', // existing and should be deleted because permissions = root
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );

        $sid = $this->createMock(SecurityIdentityInterface::class);
        $this->initSavePrivileges($extensionKey, $rootOid);

        $this->setExpectationsForGetAces(
            [
                'test:Acme\Class3' => [$class3Ace]
            ]
        );

        $this->setExpectationsForSetPermission(
            $sid,
            [
                'test:(root)'      => ['VIEW_SYSTEM', 'CREATE_BASIC'],
                'test:Acme\Class2' => ['VIEW_SYSTEM', 'CREATE_SYSTEM'],
            ]
        );
        $this->setExpectationsForDeletePermission(
            $sid,
            [
                'test:Acme\Class3' => ['VIEW_BASIC', 'CREATE_BASIC'],
            ]
        );

        $this->repository->savePrivileges($sid, $privileges);

        $this->validateExpectationsForGetAces();
        $this->validateExpectationsForSetPermission();
        $this->validateExpectationsForDeletePermission();
    }

    private static function getMask(array $masks, MaskBuilder $maskBuilder = null): int
    {
        if ($maskBuilder === null) {
            $maskBuilder = new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT']);
        }
        $maskBuilder->reset();
        foreach ($masks as $mask) {
            $maskBuilder->add($mask);
        }

        return $maskBuilder->get();
    }

    private static function getPrivilege(string $id, array $permissions): AclPrivilege
    {
        $privilege = new AclPrivilege();
        $privilege->setIdentity(new AclPrivilegeIdentity($id));
        foreach ($permissions as $name => $accessLevel) {
            $privilege->addPermission(new AclPermission($name, $accessLevel));
        }

        return $privilege;
    }

    private function getAce($mask, $sid = null): EntryInterface
    {
        $ace = $this->createMock(EntryInterface::class);
        $ace->expects(self::any())
            ->method('isGranting')
            ->willReturn(true);
        $ace->expects(self::any())
            ->method('getMask')
            ->willReturn($mask);
        if ($sid !== null) {
            $ace->expects(self::any())
                ->method('getSecurityIdentity')
                ->willReturn($sid);
        }

        return $ace;
    }

    private static function getAcls(array $src): \SplObjectStorage
    {
        $isPartial = false;
        $acls = new \SplObjectStorage();
        foreach ($src as $item) {
            if ($item['acl'] !== null) {
                $acls->attach($item['oid'], $item['acl']);
            } else {
                $isPartial = true;
            }
        }

        if ($isPartial) {
            $ex = new NotAllAclsFoundException();
            $ex->setPartialResult($acls);
            throw $ex;
        }

        return $acls;
    }
}
