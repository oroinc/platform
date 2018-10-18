<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EmailBundle\Acl\Voter\EmailVoter;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailVoter */
    protected $emailVoter;

    /** @var Container|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->with('security.authorization_checker')
            ->willReturn($this->authorizationChecker);

        $this->emailVoter = new EmailVoter($this->container);
    }

    /**
     * @param boolean $atLeastOneGranted
     * @dataProvider voteProvider
     */
    public function testVote($atLeastOneGranted)
    {
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
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

        $result = $atLeastOneGranted ? EmailVoter::ACCESS_GRANTED : EmailVoter::ACCESS_DENIED;
        $this->assertEquals($result, $this->emailVoter->vote($token, $email, $attributes));
    }

    public function voteProvider()
    {
        return [[true], [false]];
    }
}
