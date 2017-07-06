<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;

class AclVoterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|PermissionMapInterface */
    private $permissionMap;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionSelector */
    private $extensionSelector;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclGroupProviderInterface */
    private $groupProvider;

    /** @var AclVoter */
    private $voter;

    protected function setUp()
    {
        $this->permissionMap = $this->createMock(PermissionMapInterface::class);
        $this->extensionSelector = $this->createMock(AclExtensionSelector::class);
        $this->groupProvider = $this->createMock(AclGroupProviderInterface::class);

        $this->voter = new AclVoter(
            $this->createMock(AclProviderInterface::class),
            $this->createMock(ObjectIdentityRetrievalStrategyInterface::class),
            $this->createMock(SecurityIdentityRetrievalStrategyInterface::class),
            $this->permissionMap
        );
        $this->voter->setAclExtensionSelector($this->extensionSelector);
        $this->voter->setAclGroupProvider($this->groupProvider);
    }

    protected function tearDown()
    {
        unset($this->voter, $this->permissionMap, $this->extensionSelector);
    }

    /**
     * @dataProvider voteDataProvider
     *
     * @param mixed $object
     * @param mixed $expectedObject
     * @param int $expected
     * @param array $permissions
     * @param string $group
     */
    public function testVote($object, $expectedObject, $expected, array $permissions = ['test'], $group = '')
    {
        $token = $this->getToken();

        $inVoteToken = null;
        $inVoteObject = null;
        $inVoteExtension = null;

        $extension = $this->assertAclExtensionCalled($object, $permissions);

        $this->permissionMap
            ->expects($this->any())
            ->method('contains')
            ->with('test')
            ->willReturn(true);

        $this->groupProvider
            ->expects($this->any())
            ->method('getGroup')
            ->willReturn($group);

        if ($expected !== AclVoter::ACCESS_DENIED) {
            $this->permissionMap->expects($this->exactly(2))
                ->method('getMasks')
                    ->willReturnCallback(
                        function () use (&$inVoteToken, &$inVoteObject, &$inVoteExtension) {
                            $inVoteToken = $this->voter->getSecurityToken();
                            $inVoteObject = $this->voter->getObject();
                            $inVoteExtension = $this->voter->getAclExtension();

                            $this->voter->setTriggeredMask(1, AccessLevel::LOCAL_LEVEL);

                            return null;
                        }
                    );

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
                'expectedObject' => new \stdClass(),
                'expected' => AclVoter::ACCESS_ABSTAIN
            ],
            [
                'object' => new ObjectIdentity('stdClass', 'entity'),
                'expectedObject' => new ObjectIdentity('stdClass', 'entity'),
                'expected' => AclVoter::ACCESS_ABSTAIN
            ],
            [
                'object' => new ObjectIdentity('stdClass', 'test_group@entity'),
                'expectedObject' => new ObjectIdentity('stdClass', 'entity'),
                'expected' => AclVoter::ACCESS_ABSTAIN,
                'permissions' => ['test'],
                'group' => 'test_group'
            ],
            [
                'object' => new ObjectIdentity('stdClass', 'test_group@entity'),
                'expectedObject' => new ObjectIdentity('stdClass', 'entity'),
                'expected' => AclVoter::ACCESS_DENIED,
                'permissions' => ['test'],
                'group' => ''
            ],
            [
                'object' => new ObjectIdentity('stdClass', 'test_group@entity'),
                'expectedObject' => new ObjectIdentity('stdClass', 'entity'),
                'expected' => AclVoter::ACCESS_DENIED,
                'permissions' => ['new_test'],
                'group' => 'test_group'
            ]
        ];
    }

    public function testVoteWithInvalidObject()
    {
        $object = new \stdClass();

        $this->extensionSelector->expects($this->once())
            ->method('select')
            ->with($object)
            ->willThrowException(new InvalidDomainObjectException());

        $this->assertEquals(AclVoter::ACCESS_ABSTAIN, $this->voter->vote($this->getToken(), $object, []));
    }

    /**
     * @return TokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getToken()
    {
        return $this->createMock(TokenInterface::class);
    }

    /**
     * @param mixed $object
     * @param array $permissions
     * @return AclExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertAclExtensionCalled($object, array $permissions)
    {
        $extension = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        $extension->expects($this->any())
            ->method('getPermissions')
            ->willReturn($permissions);

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
}
