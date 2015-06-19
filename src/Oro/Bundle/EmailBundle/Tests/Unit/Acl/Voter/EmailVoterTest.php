<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\DependencyInjection\Container;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Acl\Voter\EmailVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailVoter
     */
    protected $emailVoter;

    /**
     * @var Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_security.security_facade')
            ->willReturn($this->securityFacade);

        $this->emailVoter = new EmailVoter($this->container);
    }

    /**
     * @param $attribute
     * @param $isSupported
     * @dataProvider attributeProvider
     */
    public function testSupportsAttribute($attribute, $isSupported)
    {
        $this->assertEquals($isSupported, $this->emailVoter->supportsAttribute($attribute));
    }

    public function attributeProvider()
    {
        return [
            ['VIEW', true],
            ['EDIT', true],
            ['CREATE', false],
            ['DELETE', false],
        ];
    }

    /**
     * @param $class
     * @param $isSupported
     * @dataProvider classProvider
     */
    public function testSupportsClass($class, $isSupported)
    {
        $this->assertEquals($isSupported, $this->emailVoter->supportsClass($class));
    }

    public function classProvider()
    {
        return [
            ['Oro\Bundle\EmailBundle\Entity\Email', true],
            ['Oro\Bundle\EmailBundle\Entity\EmailBody', true],
            ['Oro\Bundle\EmailBundle\Entity\EmailAttachment', true],
            [EmailBody::CLASS_NAME, true],
            [EmailAttachment::CLASS_NAME, true],
            ['Some\Unsupported\Class', false]
        ];
    }

    /**
     * @param boolean $atLeastOneGranted
     * @dataProvider voteProvider
     */
    public function testVote($atLeastOneGranted)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $email = new Email();
        $emailUser1 = new EmailUser();
        $emailUser2 = new EmailUser();
        $emailUser3 = new EmailUser();
        $email->addEmailUser($emailUser1);
        $email->addEmailUser($emailUser2);
        $email->addEmailUser($emailUser3);
        $attributes = ['VIEW'];

        if ($atLeastOneGranted) {
            $this->securityFacade->expects($this->once())
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
