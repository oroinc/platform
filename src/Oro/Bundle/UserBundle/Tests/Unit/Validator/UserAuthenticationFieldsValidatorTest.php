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
    public function testUsernameValidUsernameInEmailFormat()
    {
        $user = $this->getUser(1);
        $user->setUsername('username@example.com');
        $user->setEmail('username@example.com');

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

        $existingUser = ['id' => 2, 'username' => 'username', 'email' => 'username@example.com'];

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($existingUser));
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['andWhere', 'setParameter', 'getQuery'])
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $queryBuilder->expects($this->at(0))
            ->method('andWhere')
            ->with('u.email = :email')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(1))
            ->method('setParameter')
            ->with('email', 'username@example.com')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(2))
            ->method('andWhere')
            ->with('u.id <> :id')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(3))
            ->method('setParameter')
            ->with('id', 1)
            ->will($this->returnSelf());

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->will($this->returnValue($queryBuilder));
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
