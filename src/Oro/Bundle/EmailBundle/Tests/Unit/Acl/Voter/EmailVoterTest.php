<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EmailBundle\Acl\Voter\EmailVoter;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EmailVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var MailboxManager|\PHPUnit\Framework\MockObject\MockObject */
    private $mailboxManager;

    /** @var EmailVoter */
    private $emailVoter;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->mailboxManager = $this->createMock(MailboxManager::class);

        $container = TestContainerBuilder::create()
            ->add('oro_email.mailbox.manager', $this->mailboxManager)
            ->getContainer($this);

        $this->emailVoter = new EmailVoter($this->authorizationChecker, $container);
    }

    /**
     * @dataProvider voteProvider
     */
    public function testVote(bool $atLeastOneGranted)
    {
        $token = $this->createMock(TokenInterface::class);
        $email = new Email();
        $emailUser1 = new EmailUser();
        $emailUser2 = new EmailUser();
        $emailUser3 = new EmailUser();
        $email->addEmailUser($emailUser1);
        $email->addEmailUser($emailUser2);
        $email->addEmailUser($emailUser3);
        $attributes = ['VIEW'];

        if ($atLeastOneGranted) {
            $this->authorizationChecker->expects($this->once())
                ->method('isGranted')
                ->with('VIEW', ${'emailUser' . mt_rand(1, 3)})
                ->willReturn(true);
        }

        $result = $atLeastOneGranted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
        $this->assertEquals($result, $this->emailVoter->vote($token, $email, $attributes));
    }

    public function voteProvider(): array
    {
        return [[true], [false]];
    }
}
