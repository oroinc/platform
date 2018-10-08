<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AclVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|PermissionMapInterface */
    private $permissionMap;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclExtensionSelector */
    private $extensionSelector;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface */
    private $groupProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenInterface */
    private $securityToken;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PermissionMapInterface */
    private $extension;

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

        $this->securityToken = $this->createMock(TokenInterface::class);
        $this->extension = $this->createMock(AclExtensionInterface::class);
    }

    public function testOneShotIsGrantedObserver()
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::exactly(2))
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::exactly(2))
            ->method('getMasks')
            ->willReturnCallback(function () {
                $this->voter->setTriggeredMask(1, AccessLevel::LOCAL_LEVEL);

                return null;
            });

        $isGrantedObserver = $this->createMock(OneShotIsGrantedObserver::class);
        $isGrantedObserver->expects(self::once())
            ->method('setAccessLevel')
            ->with(AccessLevel::LOCAL_LEVEL);

        $this->voter->addOneShotIsGrantedObserver($isGrantedObserver);
        $this->voter->vote($this->securityToken, $object, ['test']);

        // call the vote method one more time to ensure that OneShotIsGrantedObserver was removed from the voter
        $this->voter->vote($this->securityToken, $object, ['test']);
    }

    public function testInitialState()
    {
        self::assertNull($this->voter->getSecurityToken());
        self::assertNull($this->voter->getObject());
        self::assertNull($this->voter->getAclExtension());
    }

    public function testClearStateAfterVote()
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        $this->voter->vote($this->securityToken, $object, ['test']);

        self::assertNull($this->voter->getSecurityToken());
        self::assertNull($this->voter->getObject());
        self::assertNull($this->voter->getAclExtension());
    }

    public function testClearStateAfterVoteEvenIfExceptionOccurred()
    {
        $object = new \stdClass();
        $exception = new \Exception('some error');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willThrowException($exception);

        try {
            $this->voter->vote($this->securityToken, $object, ['test']);
            self::fail('Expected that the exception is not handled');
        } catch (\Exception $e) {
            self::assertSame($exception, $e);
        }

        self::assertNull($this->voter->getSecurityToken());
        self::assertNull($this->voter->getObject());
        self::assertNull($this->voter->getAclExtension());
    }

    public function testStateOfVote()
    {
        $object = new \stdClass();

        $inVoteToken = null;
        $inVoteObject = null;
        $inVoteExtension = null;

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturnCallback(
                function () use (&$inVoteToken, &$inVoteObject, &$inVoteExtension) {
                    $inVoteToken = $this->voter->getSecurityToken();
                    $inVoteObject = $this->voter->getObject();
                    $inVoteExtension = $this->voter->getAclExtension();

                    return null;
                }
            );

        $this->voter->vote($this->securityToken, $object, ['test']);

        self::assertSame($this->securityToken, $inVoteToken);
        self::assertSame($object, $inVoteObject);
        self::assertSame($this->extension, $inVoteExtension);
    }

    public function testAclExtensionNotFound()
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn(null);
        $this->permissionMap->expects(self::never())
            ->method('contains');

        self::assertEquals(
            AclVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteAccessAbstain()
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        self::assertEquals(
            AclVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteAccessGranted()
    {
        $object = new \stdClass();

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);
        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(1);

        self::assertEquals(
            AclVoter::ACCESS_GRANTED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObject()
    {
        $object = new ObjectIdentity('stdClass', 'entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test']);

        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        self::assertEquals(
            AclVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObjectWhenObjectGroupIsNotEqualCurrentGroup()
    {
        $object = new ObjectIdentity('stdClass', 'test_group@entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::never())
            ->method('getPermissions');

        $this->permissionMap->expects(self::never())
            ->method('contains');
        $this->permissionMap->expects(self::never())
            ->method('getMasks');

        self::assertEquals(
            AclVoter::ACCESS_DENIED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObjectWhenObjectGroupIsEqualCurrentGroup()
    {
        $object = new ObjectIdentity('stdClass', 'test_group@entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('test_group');
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test']);

        $this->permissionMap->expects(self::exactly(2))
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::once())
            ->method('getMasks')
            ->willReturn(null);

        self::assertEquals(
            AclVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }

    public function testVoteForObjectIdentityObjectWhenExtensionDoesNotSupportGivenPermission()
    {
        $object = new ObjectIdentity('stdClass', 'entity');

        $this->extensionSelector->expects(self::once())
            ->method('select')
            ->with(self::identicalTo($object))
            ->willReturn($this->extension);

        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn(AclGroupProviderInterface::DEFAULT_SECURITY_GROUP);
        $this->extension->expects(self::once())
            ->method('getPermissions')
            ->with(null, false, true)
            ->willReturn(['test1']);

        $this->permissionMap->expects(self::once())
            ->method('contains')
            ->with('test')
            ->willReturn(true);
        $this->permissionMap->expects(self::never())
            ->method('getMasks');

        self::assertEquals(
            AclVoter::ACCESS_DENIED,
            $this->voter->vote($this->securityToken, $object, ['test'])
        );
    }
}
