<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Validator\UserAuthenticationFieldsValidator;
use Oro\Bundle\UserBundle\Validator\Constraints\UserAuthenticationFieldsConstraint;

class UserAuthenticationFieldsValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    protected $metadata;

    /**
     * @var UserAuthenticationFieldsConstraint
     */
    protected $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConstraintViolationBuilderInterface
     */
    protected $violation;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository
     */
    protected $repository;

    /**
     * @var UserAuthenticationFieldsValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->om));

        $this->repository = $this
            ->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->violation =
            $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface')
            ->getMock();

        $this->constraint = new UserAuthenticationFieldsConstraint();
        $this->context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->getMock();

        $this->validator = new UserAuthenticationFieldsValidator($this->registry);
        $this->validator->initialize($this->context);
    }

    public function tearDown()
    {
        unset($this->constraint, $this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_user.validator.user_authentication_fields', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    /**
     * User username = User email, Username not in email format
     */
    public function testUsernameValid()
    {
        $user = $this->getUser(1);
        $user->setUsername('username');
        $user->setEmail('username@example.com');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($user, $this->constraint);
    }

    /**
     * User username = User email, Username in email format
     */
    public function testUsernameValidUsernameAsEmail()
    {
        $user = $this->getUser(1);
        $user->setUsername('username@example.com');
        $user->setEmail('username@example.com');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($user, $this->constraint);
    }

    /**
     * User with email as current user Username not exist, Username in email format
     */
    public function testUsernameValidUsernameInEmailFormat()
    {
        $user = $this->getUser(1);
        $user->setUsername('username@example.com');
        $user->setEmail('test@example.com');

        $existingUser = null;

        $this->repository->expects($this->once())
            ->method('findExistingUserByEmail')
            ->with('username@example.com', 1)
            ->will($this->returnValue($existingUser));

        $this->om->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($user, $this->constraint);
    }

    /**
     * User username = existing user email, Username in email format
     */
    public function testUsernameNotValidUsernameInEmailFormat()
    {
        $user = $this->getUser(1);
        $user->setUsername('username@example.com');
        $user->setEmail('test@example.com');

        $existingUser = $this->getUser(2);
        $existingUser->setUsername('username');
        $existingUser->setEmail('username@example.com');

        $this->repository->expects($this->once())
            ->method('findExistingUserByEmail')
            ->with('username@example.com', 1)
            ->will($this->returnValue($existingUser));

        $this->om->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violation);

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with(UserAuthenticationFieldsValidator::VIOLATION_PATH)
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($user, $this->constraint);
    }

    /**
     * @param int|null $id
     * @return User
     */
    protected function getUser($id = null)
    {
        $user = new User();

        if (null !== $id) {
            $user->setId($id);
        }

        return $user;
    }
}
