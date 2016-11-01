<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\PasswordHistory;
use Oro\Bundle\UserBundle\Validator\Constraints\UsedPassword;
use Oro\Bundle\UserBundle\Validator\UsedPasswordValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UsedPasswordValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $constraint;

    protected function setUp()
    {

        $user = new User();
        $passHash1 = $this->createPasswordHistory($user);
        $passHash2 = $this->createPasswordHistory($user);

        $repo = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findBy')
            ->willReturn([$passHash1, $passHash2]);

        $om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $om->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($om);

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls(true, 3));

        $encoder = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $encoder->expects($this->any())
            ->method('isPasswordValid')
            ->willReturn(true);

        $encoderFactory = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $encoderFactory->expects($this->once())
            ->method('getEncoder')
            ->willReturn($encoder);

        $this->validator = new UsedPasswordValidator($registry, $configManager, $encoderFactory);
    }

    public function testValidateAlreadyUsedPassword()
    {
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->disableOriginalConstructor()->getMock();
        $context->expects($this->once())
            ->method('addViolation');
        $this->validator->initialize($context);
        $this->validator->validate('testPass123', new UsedPassword(['userId' => 123]));
    }

    private function createPasswordHistory(User $user)
    {
        $passwordHistory = new PasswordHistory();
        $passwordHistory->setUser($user);
        $passwordHistory->setSalt($user->getSalt());
        $passwordHistory->setPasswordHash($user->getPassword());

        return $passwordHistory;
    }
}
