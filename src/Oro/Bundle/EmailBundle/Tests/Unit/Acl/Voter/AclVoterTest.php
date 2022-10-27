<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EmailBundle\Acl\Voter\AclVoter;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AclVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclVoterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerVoter;

    /** @var AclVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->innerVoter = $this->createMock(AclVoterInterface::class);

        $this->voter = new AclVoter($this->innerVoter);
    }

    public function testVoteOnNonEmailUserEntity(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new \stdClass();
        $attributes = [BasicPermission::VIEW];
        $result = VoterInterface::ACCESS_GRANTED;

        $this->innerVoter->expects(self::once())
            ->method('vote')
            ->with($token, $subject, $attributes)
            ->willReturn($result);

        self::assertEquals($result, $this->voter->vote($token, $subject, $attributes));
    }

    public function testVoteOnEmailUserEntityClass(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = EmailUser::class;
        $attributes = [BasicPermission::VIEW];
        $result = VoterInterface::ACCESS_GRANTED;

        $this->innerVoter->expects(self::once())
            ->method('vote')
            ->with($token, $subject, $attributes)
            ->willReturn($result);

        self::assertEquals($result, $this->voter->vote($token, $subject, $attributes));
    }

    public function testVoteOnPublicEmailUserEntity(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new EmailUser();
        $attributes = [BasicPermission::VIEW];
        $result = VoterInterface::ACCESS_GRANTED;

        $this->innerVoter->expects(self::once())
            ->method('vote')
            ->with($token, $subject, $attributes)
            ->willReturn($result);

        self::assertEquals($result, $this->voter->vote($token, $subject, $attributes));
    }

    public function testVoteOnPrivateEmailUserEntity(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new EmailUser();
        $subject->setIsEmailPrivate(true);
        $attributes = [BasicPermission::VIEW];
        $result = VoterInterface::ACCESS_GRANTED;

        $this->innerVoter->expects(self::once())
            ->method('vote')
            ->with($token, $subject, ['VIEW_PRIVATE'])
            ->willReturn($result);

        self::assertEquals($result, $this->voter->vote($token, $subject, $attributes));
    }

    public function testVoteOnPrivateEmailUserEntityAndNotEditPermission(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new EmailUser();
        $subject->setIsEmailPrivate(true);
        $attributes = [BasicPermission::EDIT];
        $result = VoterInterface::ACCESS_GRANTED;

        $this->innerVoter->expects(self::once())
            ->method('vote')
            ->with($token, $subject, $attributes)
            ->willReturn($result);

        self::assertEquals($result, $this->voter->vote($token, $subject, $attributes));
    }
}
