<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;

class AclVoterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|PermissionMapInterface */
    private $permissionMap;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionSelector */
    private $extensionSelector;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PermissionManager */
    private $permissionManager;

    /** @var AclVoter */
    private $voter;

    protected function setUp()
    {
        $this->permissionMap = $this->getMock('Symfony\Component\Security\Acl\Permission\PermissionMapInterface');

        $this->extensionSelector = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new AclVoter(
            $this->getMock('Symfony\Component\Security\Acl\Model\AclProviderInterface'),
            $this->getMock('Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface'),
            $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface'),
            $this->permissionMap
        );
        $this->voter->setAclExtensionSelector($this->extensionSelector);
        $this->voter->setPermissionManager($this->permissionManager);
    }

    protected function tearDown()
    {
        unset($this->voter, $this->permissionMap, $this->extensionSelector, $this->permissionManager);
    }

    /**
     * @dataProvider voteDataProvider
     *
     * @param mixed $object
     * @param string $expectedGroup
     * @param mixed $expectedObject
     * @param int $expected
     */
    public function testVote($object, $expectedGroup, $expectedObject, $expected)
    {
        /** @var TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $inVoteToken = null;
        $inVoteObject = null;
        $inVoteExtension = null;

        $this->assertPermissionManagerCalled($expectedGroup);
        $extension = $this->assertAclExtensionCalled($expectedObject);

        $this->permissionMap
            ->expects($this->atLeast(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);

        if ($expected !== AclVoter::ACCESS_DENIED) {
            $this->permissionMap->expects($this->exactly(2))
                ->method('getMasks')
                    ->willReturnCallback(
                        function () use (&$inVoteToken, &$inVoteObject, &$inVoteExtension) {
                            $inVoteToken = $this->voter->getSecurityToken();
                            $inVoteObject = $this->voter->getObject();
                            $inVoteExtension = $this->voter->getAclExtension();

                            $this->voter->setTriggeredMask(1);

                            return null;
                        }
                    );

            $extension->expects($expected === AclVoter::ACCESS_DENIED ? $this->never() : $this->once())
                ->method('getAccessLevel')
                ->with(1)
                ->willReturn(AccessLevel::LOCAL_LEVEL);

            $this->assertIsGrantedObserverCalled();
        }

        $this->assertNull($this->voter->getSecurityToken());
        $this->assertNull($this->voter->getObject());
        $this->assertNull($this->voter->getAclExtension());

        $this->assertEquals($expected, $this->voter->vote($token, $object, ['test']));

        $this->assertNull($this->voter->getSecurityToken());
        $this->assertNull($this->voter->getObject());
        $this->assertNull($this->voter->getAclExtension());

        if ($expected !== AclVoter::ACCESS_DENIED) {
            $this->assertSame($token, $inVoteToken);
            $this->assertEquals($expectedObject, $inVoteObject);
            $this->assertSame($extension, $inVoteExtension);
        }

        // call the vote method one more time to ensure that OneShotIsGrantedObserver was removed from the voter
        $this->assertEquals($expected, $this->voter->vote($token, $object, ['test']));
    }

    /**
     * @return array
     */
    public function voteDataProvider()
    {
        return [
            [
                'object' => new \stdClass(),
                'expectedGroup' => null,
                'expectedObject' => new \stdClass(),
                'expected' => AclVoter::ACCESS_ABSTAIN,
                'permissionManagerCalls' => 0
            ],
            [
                'object' => new ObjectIdentity('stdClass', 'entity'),
                'expectedGroup' => '',
                'expectedObject' => new ObjectIdentity('stdClass', 'entity'),
                'expected' => AclVoter::ACCESS_ABSTAIN
            ],
            [
                'object' => new ObjectIdentity('stdClass', 'test_group@entity'),
                'expectedGroup' => 'test_group',
                'expectedObject' => new ObjectIdentity('stdClass', 'entity'),
                'expected' => AclVoter::ACCESS_ABSTAIN
            ],
            [
                'object' => new ObjectIdentity('stdClass', 'test_group@entity'),
                'expectedGroup' => 'new_group',
                'expectedObject' => new ObjectIdentity('stdClass', 'entity'),
                'expected' => AclVoter::ACCESS_DENIED,
                'permissionManagerCalls' => 0
            ]
        ];
    }

    /**
     * @param mixed $object
     * @return \PHPUnit_Framework_MockObject_MockObject|AclExtensionInterface
     */
    protected function assertAclExtensionCalled($object)
    {
        $extension = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');

        $this->extensionSelector->expects($this->exactly(2))
            ->method('select')
            ->with($object)
            ->willReturn($extension);

        return $extension;
    }

    protected function assertIsGrantedObserverCalled()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OneShotIsGrantedObserver $isGrantedObserver */
        $isGrantedObserver = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver')
            ->disableOriginalConstructor()
            ->getMock();
        $isGrantedObserver->expects($this->once())
            ->method('setAccessLevel')
            ->with(AccessLevel::LOCAL_LEVEL);

        $this->voter->addOneShotIsGrantedObserver($isGrantedObserver);
    }

    /**
     * @param string $expectedGroup
     */
    protected function assertPermissionManagerCalled($expectedGroup)
    {
        $this->permissionManager->expects($this->any())
            ->method('getPermissionsMap')
            ->willReturnCallback(
                function ($group) use ($expectedGroup) {
                    return $group === $expectedGroup ? ['test' => 1] : [];
                }
            );
    }
}
