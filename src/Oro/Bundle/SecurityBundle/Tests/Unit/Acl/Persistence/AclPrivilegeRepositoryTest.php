<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity as OID;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclPrivilegeRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclPrivilegeRepository */
    private $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $extensionSelector;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $aceProvider;

    protected function setUp()
    {
        $this->extension = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        $this->extension->expects($this->any())
            ->method('getObjectIdentity')
            ->will(
                $this->returnCallback(
                    function ($object) {
                        return new ObjectIdentity(
                            substr($object, 0, strpos($object, ':')),
                            substr($object, strpos($object, ':') + 1)
                        );
                    }
                )
            );
        $this->extension->expects($this->any())
            ->method('getMaskBuilder')
            ->will($this->returnValue(new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT'])));
        $this->extension->expects($this->any())
            ->method('getAllMaskBuilders')
            ->will($this->returnValue(
                [new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT'])]
            ));

        $this->extensionSelector = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extensionSelector->expects($this->any())
            ->method('select')
            ->will($this->returnValue($this->extension));
        $this->extensionSelector->expects($this->any())
            ->method('selectByExtensionKey')
            ->will($this->returnValue($this->extension));

        $this->aceProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AceManipulationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager->expects($this->any())
            ->method('getExtensionSelector')
            ->will($this->returnValue($this->extensionSelector));
        $this->manager->expects($this->any())
            ->method('getAllExtensions')
            ->will($this->returnValue([$this->extension]));
        $this->manager->expects($this->any())
            ->method('getAceProvider')
            ->will($this->returnValue($this->aceProvider));

        $this->repository = new AclPrivilegeRepository($this->manager);
    }

    public function testGetPermissionNames()
    {
        $extensionKey = 'test';
        $permissions = ['VIEW', 'EDIT'];

        $this->extension->expects($this->once())
            ->method('getPermissions')
            ->will($this->returnValue($permissions));

        $this->assertEquals(
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

        $this->extension->expects($this->at(0))
            ->method('getPermissions')
            ->will($this->returnValue($permissions1));
        $this->extension->expects($this->at(1))
            ->method('getPermissions')
            ->will($this->returnValue($permissions2));

        $this->assertEquals(
            ['VIEW', 'EDIT', 'CREATE'],
            $this->repository->getPermissionNames([$extensionKey1, $extensionKey2])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetPrivileges()
    {
        $thisLink = $this;

        $sid = $this->createMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface');
        $sid->expects($this->any())->method('equals')->will($this->returnValue(true));

        $extensionKey = 'test';
        $classes = [
            'Acme\Class1',
            'Acme\Class2',
        ];
        $class1 = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo');
        $class1->expects($this->once())->method('getClassName')->will($this->returnValue($classes[0]));
        $class1->expects($this->once())->method('getGroup')->will($this->returnValue('SomeGroup'));
        $class1->expects($this->once())->method('getLabel')->will($this->returnValue('Class 1'));
        $class1->expects($this->once())->method('getDescription')->will($this->returnValue('Desc 1'));
        $class1->expects($this->once())->method('getCategory')->will($this->returnValue('Category 1'));
        $class2 = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo');
        $class2->expects($this->once())->method('getClassName')->will($this->returnValue($classes[1]));
        $class2->expects($this->once())->method('getGroup')->will($this->returnValue('SomeGroup'));
        $class2->expects($this->once())->method('getLabel')->will($this->returnValue('Class 2'));
        $class2->expects($this->once())->method('getDescription')->will($this->returnValue('Desc 2'));
        $class2->expects($this->once())->method('getCategory')->will($this->returnValue('Category 2'));

        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $rootAcl = $this->createMock('Symfony\Component\Security\Acl\Model\AclInterface');

        $oid1 = new ObjectIdentity($extensionKey, $classes[0]);
        $oid1Acl = $this->createMock('Symfony\Component\Security\Acl\Model\AclInterface');
        $oid2 = new ObjectIdentity($extensionKey, $classes[1]);

        $oidsWithRoot = [$rootOid, $oid2, $oid1];

        $aclsSrc = [
            ['oid' => $rootOid, 'acl' => $rootAcl],
            ['oid' => $oid1, 'acl' => $oid1Acl],
            ['oid' => $oid2, 'acl' => null],
        ];

        $allowedPermissions = [];
        $allowedPermissions[(string) $rootOid] = ['VIEW', 'CREATE', 'EDIT'];
        $allowedPermissions[(string) $oid1] = ['VIEW', 'CREATE', 'EDIT'];
        $allowedPermissions[(string) $oid2] = ['VIEW', 'CREATE'];

        $rootAce = $this->getAce('root', $sid);
        $rootAcl->expects($this->any())
            ->method('getObjectAces')
            ->will($this->returnValue([$rootAce]));
        $rootAcl->expects($this->never())
            ->method('getClassAces');

        $oid1Ace = $this->getAce('oid1', $sid);
        $oid1Acl->expects($this->any())
            ->method('getClassAces')
            ->will($this->returnValue([$oid1Ace]));
        $oid1Acl->expects($this->once())
            ->method('getObjectAces')
            ->will($this->returnValue([]));

        $this->extension->expects($this->once())
            ->method('getExtensionKey')
            ->will($this->returnValue($extensionKey));
        $this->extension->expects($this->once())
            ->method('getClasses')
            ->will($this->returnValue([$class2, $class1]));
        $this->extension->expects($this->any())
            ->method('getAllowedPermissions')
            ->will(
                $this->returnCallback(
                    function ($oid) use (&$allowedPermissions) {
                        return $allowedPermissions[(string) $oid];
                    }
                )
            );
        $this->extension->expects($this->any())
            ->method('adaptRootMask')
            ->will(
                $this->returnCallback(
                    function ($mask, $object) {
                        if ($mask === 'root' && $object === 'test:Acme\Class2') {
                            return 'adaptedRoot';
                        }

                        return $mask;
                    }
                )
            );
        $this->extension->expects($this->any())
            ->method('getPermissions')
            ->will($this->returnValue(['VIEW', 'CREATE', 'EDIT']));
        $this->extension->expects($this->any())
            ->method('getAccessLevel')
            ->will(
                $this->returnCallback(
                    function ($mask, $permission) {
                        switch ($permission) {
                            case 'VIEW':
                                if ($mask === 'root') {
                                    return AccessLevel::GLOBAL_LEVEL;
                                } elseif ($mask === 'oid1') {
                                    return AccessLevel::BASIC_LEVEL;
                                }
                                break;
                            case 'CREATE':
                                if ($mask === 'root') {
                                    return AccessLevel::DEEP_LEVEL;
                                } elseif ($mask === 'oid1') {
                                    return AccessLevel::BASIC_LEVEL;
                                }
                                break;
                            case 'EDIT':
                                if ($mask === 'root') {
                                    return AccessLevel::LOCAL_LEVEL;
                                } elseif ($mask === 'oid1') {
                                    return AccessLevel::NONE_LEVEL;
                                }
                                break;
                        }
                        if ($mask === 'adaptedRoot') {
                            return AccessLevel::SYSTEM_LEVEL;
                        }

                        return AccessLevel::NONE_LEVEL;
                    }
                )
            );

        $this->manager->expects($this->once())
            ->method('getRootOid')
            ->with($this->equalTo($extensionKey))
            ->will($this->returnValue($rootOid));

        $this->manager->expects($this->once())
            ->method('findAcls')
            ->with($this->identicalTo($sid), $this->equalTo($oidsWithRoot))
            ->will(
                $this->returnCallback(
                    function () use (&$thisLink, &$aclsSrc) {
                        return $thisLink->getAcls($aclsSrc);
                    }
                )
            );

        $this->aceProvider->expects($this->any())
            ->method('getAces')
            ->will(
                $this->returnCallback(
                    function ($acl, $type, $field) use (&$rootAcl, &$oid1Acl) {
                        if ($acl === $oid1Acl) {
                            $a = $oid1Acl;
                        } else {
                            $a = $rootAcl;
                        }

                        return $a->{"get{$type}Aces"}();
                    }
                )
            );

        $result = $this->repository->getPrivileges($sid);

        $this->assertCount(count($classes), $result);
        $this->assertEquals('test:Acme\Class1', $result[0]->getIdentity()->getId());
        $this->assertEquals('Class 1', $result[0]->getIdentity()->getName());
        $this->assertEquals('SomeGroup', $result[0]->getGroup());
        $this->assertEquals('Desc 1', $result[0]->getDescription());
        $this->assertEquals('Category 1', $result[0]->getCategory());
        $this->assertEquals($extensionKey, $result[0]->getExtensionKey());
        $this->assertEquals('test:Acme\Class2', $result[1]->getIdentity()->getId());
        $this->assertEquals('Class 2', $result[1]->getIdentity()->getName());
        $this->assertEquals('SomeGroup', $result[1]->getGroup());
        $this->assertEquals('Desc 2', $result[1]->getDescription());
        $this->assertEquals('Category 2', $result[1]->getCategory());
        $this->assertEquals($extensionKey, $result[1]->getExtensionKey());

        $this->assertEquals(3, $result[0]->getPermissionCount());
        $this->assertEquals(2, $result[1]->getPermissionCount());

        $p = $result[0]->getPermissions();
        $this->assertEquals(AccessLevel::BASIC_LEVEL, $p['VIEW']->getAccessLevel());
        $this->assertEquals(AccessLevel::BASIC_LEVEL, $p['CREATE']->getAccessLevel());
        $this->assertEquals(AccessLevel::NONE_LEVEL, $p['EDIT']->getAccessLevel());

        $p = $result[1]->getPermissions();
        $this->assertEquals(AccessLevel::SYSTEM_LEVEL, $p['VIEW']->getAccessLevel());
        $this->assertEquals(AccessLevel::SYSTEM_LEVEL, $p['CREATE']->getAccessLevel());
        $this->assertFalse($p->containsKey('EDIT'));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider getPrivilegesWithFieldsDataProvider
     * @param FieldSecurityMetadata[] $fields
     * @param ArrayCollection         $expectation
     */
    public function testGetPrivilegesWithFields($fields, $expectation)
    {
        $thisLink = $this;
        $sid = new RoleSecurityIdentity('ROLE_ADMINISTRATOR');
        $extensionKey = 'test';

        $className = 'Acme\Class1';

        $class1 = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo');
        $class1->expects($this->exactly(2))->method('getClassName')->will($this->returnValue($className));
        $class1->expects($this->once())->method('getGroup')->will($this->returnValue('SomeGroup'));
        $class1->expects($this->once())->method('getLabel')->will($this->returnValue('Class 1'));
        $class1->expects($this->once())->method('getDescription')->will($this->returnValue('Desc 1'));
        $class1->expects($this->once())->method('getCategory')->will($this->returnValue('Category 1'));
        $class1->expects($this->exactly(2))->method('getFields')->will($this->returnValue($fields));

        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $rootAcl = $this->createMock('Symfony\Component\Security\Acl\Model\AclInterface');

        $oid1 = new ObjectIdentity($extensionKey, $className);
        $oid1Acl = $this->createMock('Symfony\Component\Security\Acl\Model\AclInterface');
        $oidsWithRoot = [$rootOid, $oid1];

        $aclsSrc = [
            ['oid' => $rootOid, 'acl' => $rootAcl],
            ['oid' => $oid1,    'acl' => $oid1Acl]
        ];

        $allowedPermissions = [
            (string) $rootOid => ['VIEW', 'CREATE', 'EDIT'],
            (string) $oid1    => ['VIEW', 'CREATE', 'EDIT']
        ];

        $rootAcl->expects($this->never())->method('getClassAces');

        $oid1Ace = $this->getAce('oid1', $sid);
        $oid1Acl->expects($this->any())
            ->method('getClassAces')
            ->will($this->returnValue([$oid1Ace]));
        $oid1Acl->expects($this->any())
            ->method('getObjectAces')
            ->will($this->returnValue([]));

        $this->extension->expects($this->exactly(2))
            ->method('getExtensionKey')
            ->will($this->returnValue($extensionKey));
        $this->extension->expects($this->once())
            ->method('getClasses')
            ->will($this->returnValue([$class1]));
        $this->extension->expects($this->any())
            ->method('getAllowedPermissions')
            ->will($this->returnCallback(function ($oid) use (&$allowedPermissions) {
                return $allowedPermissions[(string) $oid];
            }));
        $this->extension->expects($this->any())
            ->method('getPermissions')
            ->will($this->returnValue(['VIEW', 'CREATE', 'EDIT']));
        $this->extension->expects($this->any())
            ->method('getAccessLevel')
            ->will($this->returnCallback(function ($mask, $permission) {
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
            }));
        $this->extension->expects($this->any())
            ->method('getFieldExtension')
            ->willReturnSelf();

        $this->manager->expects($this->once())
            ->method('getRootOid')
            ->with($this->equalTo($extensionKey))
            ->will($this->returnValue($rootOid));
        $this->manager->expects($this->at(2))
            ->method('findAcls')
            ->with($this->identicalTo($sid), $this->equalTo($oidsWithRoot))
            ->will($this->returnCallback(function () use (&$thisLink, &$aclsSrc) {
                return $thisLink->getAcls($aclsSrc);
            }));
        $this->manager->expects($this->at(5))
            ->method('findAcls')
            ->with($this->identicalTo($sid), $this->equalTo([new OID($extensionKey, $className)]))
            ->will($this->returnCallback(function () use (&$thisLink, &$aclsSrc) {
                return $thisLink->getAcls($aclsSrc);
            }));
        $this->manager->expects($this->exactly(2))
            ->method('findAcls');

        $this->aceProvider->expects($this->any())
            ->method('getAces')
            ->will($this->returnCallback(function ($acl, $type, $field) use (&$rootAcl, &$oid1Acl) {
                return $acl === $oid1Acl
                    ? $oid1Acl->{"get{$type}Aces"}()
                    : $rootAcl->{"get{$type}Aces"}();
            }));

        $result = $this->repository->getPrivileges($sid);
        $this->assertCount(1, $result);
        $this->assertEquals(
            $extensionKey . ':' . $className,
            $result[0]->getIdentity()->getId()
        );
        $this->assertEquals($expectation, $result[0]->getFields());
    }

    /**
     * @return array
     */
    public function getPrivilegesWithFieldsDataProvider()
    {
        $fieldPrivilege1 = new AclPrivilege();
        $fieldPrivilege1->setIdentity(new AclPrivilegeIdentity('test:Acme\Class1::field1', 'field1Label'));
        $fieldPrivilege1->addPermission(new AclPermission('VIEW', 1));
        $fieldPrivilege1->addPermission(new AclPermission('CREATE', 1));
        $fieldPrivilege1->addPermission(new AclPermission('EDIT', 0));

        $fieldPrivilege2 = new AclPrivilege();
        $fieldPrivilege2->setIdentity(new AclPrivilegeIdentity('test:Acme\Class1::field2', 'field2Label'));
        $fieldPrivilege2->addPermission(new AclPermission('VIEW', 1));
        $fieldPrivilege2->addPermission(new AclPermission('CREATE', 1));
        $fieldPrivilege2->addPermission(new AclPermission('EDIT', 0));

        return [
            'with two regular fields' => [
                'fields' => [
                    new FieldSecurityMetadata('field1', 'field1Label'),
                    new FieldSecurityMetadata('field2', 'field2Label'),
                ],
                'expectation' => new ArrayCollection([$fieldPrivilege1, $fieldPrivilege2])
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
                'expectation' => new ArrayCollection([$fieldPrivilege1, $fieldPrivilege2])
            ],
            'all fields are hidden' => [
                'fields' => [
                    new FieldSecurityMetadata('field1', 'field1Label', [], null, null, true),
                    new FieldSecurityMetadata('field2', 'field2Label', [], null, null, true)
                ],
                'expectation' => new ArrayCollection()
            ]
        ];
    }

    private function initSavePrivileges($extensionKey, $rootOid)
    {
        $this->extension->expects($this->any())
            ->method('getExtensionKey')
            ->will($this->returnValue($extensionKey));
        $this->extension->expects($this->any())
            ->method('getPermissions')
            ->will($this->returnValue(['VIEW', 'CREATE', 'EDIT']));
        $this->extension->expects($this->any())
            ->method('adaptRootMask')
            ->will(
                $this->returnCallback(
                    function ($mask, $object) {
                        return $mask;
                    }
                )
            );

        $this->manager->expects($this->any())
            ->method('getRootOid')
            ->with($this->equalTo($extensionKey))
            ->will($this->returnValue($rootOid));

        $this->manager->expects($this->once())
            ->method('flush');
    }

    private $expectationsForSetPermission;
    private $triggeredExpectationsForSetPermission;

    private function validateExpectationsForSetPermission()
    {
        foreach ($this->expectationsForSetPermission as $expectedOid => $expectedMasks) {
            if (!isset($this->triggeredExpectationsForSetPermission[$expectedOid])) {
                throw new \RuntimeException(sprintf('Expected call of "setPermission" for %s.', $expectedOid));
            }
        }
    }

    private function setExpectationsForSetPermission($sid, array $expectations)
    {
        $thisLink = $this;

        $this->expectationsForSetPermission = $expectations;
        $this->triggeredExpectationsForSetPermission = [];
        $triggeredExpectationsForSetPermission = &$this->triggeredExpectationsForSetPermission;
        $this->manager->expects($this->any())
            ->method('setPermission')
            ->with($this->identicalTo($sid))
            ->will(
                $this->returnCallback(
                    function (
                        $sid,
                        $oid,
                        $mask
                    ) use (
                        &$thisLink,
                        &$expectations,
                        &$triggeredExpectationsForSetPermission
                    ) {
                        /** @var ObjectIdentity $oid */
                        $expectedMask = null;

                        foreach ($expectations as $expectedOid => $expectedMasks) {
                            if ($expectedOid === $oid->getIdentifier() . ':' . $oid->getType()) {
                                $expectedMask = $thisLink->getMask($expectedMasks);
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
                )
            );
    }

    private $expectationsForDeletePermission;
    private $triggeredExpectationsForDeletePermission;

    private function validateExpectationsForDeletePermission()
    {
        foreach ($this->expectationsForDeletePermission as $expectedOid => $expectedMasks) {
            if (!isset($this->triggeredExpectationsForDeletePermission[$expectedOid])) {
                throw new \RuntimeException(sprintf('Expected call of "deletePermission" for %s.', $expectedOid));
            }
        }
    }

    private function setExpectationsForDeletePermission($sid, array $expectations)
    {
        $thisLink = $this;

        $this->expectationsForDeletePermission = $expectations;
        $this->triggeredExpectationsForDeletePermission = [];
        $triggeredExpectationsForDeletePermission = &$this->triggeredExpectationsForDeletePermission;
        $this->manager->expects($this->any())
            ->method('deletePermission')
            ->with($this->identicalTo($sid))
            ->will(
                $this->returnCallback(
                    function (
                        $sid,
                        $oid,
                        $mask
                    ) use (
                        &$thisLink,
                        &$expectations,
                        &$triggeredExpectationsForDeletePermission
                    ) {
                        /** @var ObjectIdentity $oid */
                        $expectedMask = null;

                        foreach ($expectations as $expectedOid => $expectedMasks) {
                            if ($expectedOid === $oid->getIdentifier() . ':' . $oid->getType()) {
                                $expectedMask = $thisLink->getMask($expectedMasks);
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
                )
            );
    }

    private $expectationsForGetAces;
    private $triggeredExpectationsForGetAces;

    private function validateExpectationsForGetAces()
    {
        foreach ($this->expectationsForGetAces as $expectedOid => $expectedMasks) {
            if (!isset($this->triggeredExpectationsForGetAces[$expectedOid])) {
                throw new \RuntimeException(sprintf('Expected call of "getAces" for %s.', $expectedOid));
            }
        }
    }

    private function setExpectationsForGetAces(array $expectations)
    {
        $this->expectationsForGetAces = $expectations;
        $this->triggeredExpectationsForGetAces = [];
        $triggeredExpectationsForGetAces = &$this->triggeredExpectationsForGetAces;
        $this->manager->expects($this->any())
            ->method('getAces')
            ->will(
                $this->returnCallback(
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
                )
            );
    }

    public function testSavePrivilegesForNewRoleWithoutRoot()
    {
        $extensionKey = 'test';
        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);

        $privileges = new ArrayCollection();
        $privileges[] = self::getPrivilege(
            'test:Acme\Class1',
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );

        $sid = $this->createMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface');
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

    public function testSavePrivilegesForNewRoleWithRoot()
    {
        $extensionKey = 'test';
        $rootOid = new ObjectIdentity($extensionKey, ObjectIdentityFactory::ROOT_IDENTITY_TYPE);

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

        $sid = $this->createMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface');
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

    public function testSavePrivilegesForExistingRole()
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

        $sid = $this->createMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface');
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

    public static function getMask(array $masks, MaskBuilder $maskBuilder = null)
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

    /**
     * @param  string $id
     * @param  array  $permissions
     * @return AclPrivilege
     */
    public static function getPrivilege($id, array $permissions)
    {
        $privilege = new AclPrivilege();
        $privilege->setIdentity(new AclPrivilegeIdentity($id));
        foreach ($permissions as $name => $accessLevel) {
            $privilege->addPermission(new AclPermission($name, $accessLevel));
        }

        return $privilege;
    }

    public function getAce($mask, $sid = null)
    {
        $ace = $this->createMock('Symfony\Component\Security\Acl\Model\EntryInterface');
        $ace->expects($this->any())->method('isGranting')->will($this->returnValue(true));
        $ace->expects($this->any())->method('getMask')->will($this->returnValue($mask));
        if ($sid !== null) {
            $ace->expects($this->any())->method('getSecurityIdentity')->will($this->returnValue($sid));
        }

        return $ace;
    }

    /**
     * @param  array $src
     * @return \SplObjectStorage
     * @throws NotAllAclsFoundException
     */
    public static function getAcls(array $src)
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
