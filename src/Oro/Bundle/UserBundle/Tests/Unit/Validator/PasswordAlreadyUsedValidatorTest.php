<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\PasswordHash;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordAlreadyUsed;
use Oro\Bundle\UserBundle\Validator\PasswordAlreadyUsedValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PasswordAlreadyUsedValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $constraint;

    protected function setUp()
    {

        $user = new User();
        $passHash1 = $this->createPasswordHash($user);
        $passHash2 = $this->createPasswordHash($user);

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

        $this->validator = new PasswordAlreadyUsedValidator($registry, $configManager, $encoderFactory);
    }

    public function testValidateAlreadyUsedPassword()
    {
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->disableOriginalConstructor()->getMock();
        $context->expects($this->once())
            ->method('addViolation');
        $this->validator->initialize($context);
        $this->validator->validate('testPass123', new PasswordAlreadyUsed(['userId' => 123]));
    }

    private function createPasswordHash(User $user)
    {
        $passwordHash = new PasswordHash();
        $passwordHash->setUser($user);
        $passwordHash->setSalt($user->getSalt());
        $passwordHash->setHash($user->getPassword());

        return $passwordHash;
    }
}
