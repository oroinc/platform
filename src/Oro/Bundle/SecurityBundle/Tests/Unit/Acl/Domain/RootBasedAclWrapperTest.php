<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclWrapper;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;

class RootBasedAclWrapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $acl;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $rootAcl;

    protected function setUp()
    {
        $this->acl = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $this->rootAcl = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Acl')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetClassAces()
    {
        $context = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface');
        $aclExtension = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        $permissionGrantingStrategy = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy')
            ->disableOriginalConstructor()
            ->getMock();
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

        $obj = new RootBasedAclWrapper($acl, $rootAcl);
        $acl->insertClassAce($sid1, 1, 0); // new ACE
        $acl->insertClassAce($sid1, 256 + 1, 1); // new ACE, with service bits
        $acl->insertClassAce($sid2, 2, 2); // override root ACE
        $acl->insertClassAce($sid2, 256 + 2, 3); // override root ACE, with service bits
        $acl->insertClassAce($sid3, 4, 4); // new ACE, root ACL does not have ACE for this SID
        $rootAcl->insertObjectAce($sid2, 1, 0);
        $rootAcl->insertObjectAce($sid2, 256 + 1, 1);
        $rootAcl->insertObjectAce($sid2, 256*2 + 1, 2); // ACE existing only in root ACL
        $rootAcl->insertObjectAce($sid4, 8, 3); // ACL does not have ACE for this SID

        $aclExtension->expects($this->any())
            ->method('getServiceBits')
            ->willReturnCallback(
                function ($mask) {
                    return $mask & (~255);
                }
            );

        /** @var EntryInterface[] $resultAces */
        $resultAces = $obj->getClassAces();
        $resultMasks = [];
        foreach ($resultAces as $ace) {
            $resultMasks[] = $ace->getMask();
        }
        $this->assertEquals(
            [
                1,
                256 + 1,
                2,
                256 + 2,
                4,
                256*2 + 1,
                8
            ],
            $resultMasks
        );
    }

    public function testGetClassFieldAces()
    {
        $fieldName = 'testField';

        $context = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface');
        $aclExtension = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        $permissionGrantingStrategy = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy')
            ->disableOriginalConstructor()
            ->getMock();
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

        $obj = new RootBasedAclWrapper($acl, $rootAcl);
        $acl->insertClassFieldAce($fieldName, $sid1, 1, 0); // new ACE
        $acl->insertClassFieldAce($fieldName, $sid1, 256 + 1, 1); // new ACE, with service bits
        $acl->insertClassFieldAce($fieldName, $sid2, 2, 2); // override root ACE
        $acl->insertClassFieldAce($fieldName, $sid2, 256 + 2, 3); // override root ACE, with service bits
        $acl->insertClassFieldAce($fieldName, $sid3, 4, 4); // new ACE, root ACL does not have ACE for this SID
        $rootAcl->insertObjectFieldAce($fieldName, $sid2, 1, 0);
        $rootAcl->insertObjectFieldAce($fieldName, $sid2, 256 + 1, 1);
        $rootAcl->insertObjectFieldAce($fieldName, $sid2, 256*2 + 1, 2); // ACE existing only in root ACL
        $rootAcl->insertObjectFieldAce($fieldName, $sid4, 8, 3); // ACL does not have ACE for this SID

        $aclExtension->expects($this->any())
            ->method('getServiceBits')
            ->willReturnCallback(
                function ($mask) {
                    return $mask & (~255);
                }
            );

        /** @var EntryInterface[] $resultAces */
        $resultAces = $obj->getClassFieldAces($fieldName);
        $resultMasks = [];
        foreach ($resultAces as $ace) {
            $resultMasks[] = $ace->getMask();
        }
        $this->assertEquals(
            [
                1,
                256 + 1,
                2,
                256 + 2,
                4,
                256*2 + 1,
                8
            ],
            $resultMasks
        );
    }

    public function testGetObjectAces()
    {
        $ace = $this->createMock('Symfony\Component\Security\Acl\Model\EntryInterface');

        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $this->acl->expects($this->once())
            ->method('getObjectAces')
            ->will($this->returnValue(array($ace)));
        $result = $obj->getObjectAces();

        $this->assertEquals(array($ace), $result);
    }

    public function testGetObjectFieldAces()
    {
        $ace = $this->createMock('Symfony\Component\Security\Acl\Model\EntryInterface');

        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $this->acl->expects($this->once())
            ->method('getObjectFieldAces')
            ->with($this->equalTo('SomeField'))
            ->will($this->returnValue(array($ace)));
        $result = $obj->getObjectFieldAces('SomeField');

        $this->assertEquals(array($ace), $result);
    }

    public function testGetObjectIdentity()
    {
        $id = new ObjectIdentity('1', 'SomeType');
        $this->acl->expects($this->once())
            ->method('getObjectAces')
            ->will($this->returnValue(array('test')));
        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $this->acl->expects($this->once())
            ->method('getObjectIdentity')
            ->will($this->returnValue($id));
        $result = $obj->getObjectIdentity();

        $this->assertTrue($id === $result);
    }

    public function testGetParentAcl()
    {
        $parentAcl = $this->getMockBuilder('Symfony\Component\Security\Acl\Domain\Acl')
            ->disableOriginalConstructor()
            ->getMock();

        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $this->acl->expects($this->once())
            ->method('getParentAcl')
            ->will($this->returnValue($parentAcl));
        $result = $obj->getParentAcl();

        $this->assertTrue($parentAcl === $result);
    }

    public function testIsEntriesInheriting()
    {
        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $this->acl->expects($this->once())
            ->method('isEntriesInheriting')
            ->will($this->returnValue(true));
        $result = $obj->isEntriesInheriting();

        $this->assertTrue($result);
    }

    public function testIsSidLoaded()
    {
        $sid = new RoleSecurityIdentity('sid1');

        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $this->acl->expects($this->once())
            ->method('isSidLoaded')
            ->with($this->identicalTo($sid))
            ->will($this->returnValue(true));
        $result = $obj->isSidLoaded($sid);

        $this->assertTrue($result);
    }

    public function testIsGranted()
    {
        $sid = new RoleSecurityIdentity('sid1');

        $strategy = $this->createMock('Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface');

        $obj = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclWrapper')
            ->setConstructorArgs(array($this->acl, $this->rootAcl))
            ->setMethods(array('getPermissionGrantingStrategy'))
            ->getMock();
        $obj->expects($this->once())
            ->method('getPermissionGrantingStrategy')
            ->will($this->returnValue($strategy));
        $strategy->expects($this->once())
            ->method('isGranted')
            ->with(
                $this->identicalTo($obj),
                $this->equalTo(array(1)),
                $this->equalTo(array($sid)),
                $this->equalTo(true)
            )
            ->will($this->returnValue(true));

        $result = $obj->isGranted(array(1), array($sid), true);

        $this->assertTrue($result);
    }

    public function testIsFieldGranted()
    {
        $sid = new RoleSecurityIdentity('sid1');

        $strategy = $this->createMock('Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface');

        $obj = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\RootBasedAclWrapper')
            ->setConstructorArgs(array($this->acl, $this->rootAcl))
            ->setMethods(array('getPermissionGrantingStrategy'))
            ->getMock();
        $obj->expects($this->once())
            ->method('getPermissionGrantingStrategy')
            ->will($this->returnValue($strategy));
        $strategy->expects($this->once())
            ->method('isFieldGranted')
            ->with(
                $this->identicalTo($obj),
                $this->equalTo('SomeField'),
                $this->equalTo(array(1)),
                $this->equalTo(array($sid)),
                $this->equalTo(true)
            )
            ->will($this->returnValue(true));

        $result = $obj->isFieldGranted('SomeField', array(1), array($sid), true);

        $this->assertTrue($result);
    }

    /**
     * @expectedException \LogicException
     */
    public function testSerialize()
    {
        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $obj->serialize();
    }

    /**
     * @expectedException \LogicException
     */
    public function testUnserialize()
    {
        $obj = new RootBasedAclWrapper($this->acl, $this->rootAcl);
        $obj->unserialize('');
    }
}
