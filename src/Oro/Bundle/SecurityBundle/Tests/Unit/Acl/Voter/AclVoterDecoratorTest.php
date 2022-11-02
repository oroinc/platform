<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterDecorator;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AclVoterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclVoterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $wrapped;

    /** @var AclVoterDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->wrapped = $this->createMock(AclVoterInterface::class);

        $this->decorator = new class($this->wrapped) extends AclVoterDecorator {
        };
    }

    public function testVote(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new \stdClass();
        $attributes = [BasicPermission::VIEW];
        $result = VoterInterface::ACCESS_GRANTED;

        $this->wrapped->expects(self::once())
            ->method('vote')
            ->with($token, $subject, $attributes)
            ->willReturn($result);

        self::assertEquals($result, $this->decorator->vote($token, $subject, $attributes));
    }

    public function testAddOneShotIsGrantedObserver(): void
    {
        $observer = $this->createMock(OneShotIsGrantedObserver::class);

        $this->wrapped->expects(self::once())
            ->method('addOneShotIsGrantedObserver')
            ->with($observer);

        $this->decorator->addOneShotIsGrantedObserver($observer);
    }

    public function testGetSecurityToken(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->wrapped->expects(self::once())
            ->method('getSecurityToken')
            ->willReturn($token);

        self::assertSame($token, $this->decorator->getSecurityToken());
    }

    public function testGetAclExtension(): void
    {
        $aclExtension = $this->createMock(AclExtensionInterface::class);

        $this->wrapped->expects(self::once())
            ->method('getAclExtension')
            ->willReturn($aclExtension);

        self::assertSame($aclExtension, $this->decorator->getAclExtension());
    }

    public function testGetObject(): void
    {
        $object = new \stdClass();

        $this->wrapped->expects(self::once())
            ->method('getObject')
            ->willReturn($object);

        self::assertSame($object, $this->decorator->getObject());
    }

    public function testSetTriggeredMask(): void
    {
        $mask = 1;
        $accessLevel = 2;

        $this->wrapped->expects(self::once())
            ->method('setTriggeredMask')
            ->with($mask, $accessLevel);

        $this->decorator->setTriggeredMask($mask, $accessLevel);
    }
}
