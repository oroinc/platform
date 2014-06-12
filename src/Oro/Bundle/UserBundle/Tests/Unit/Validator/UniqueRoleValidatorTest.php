<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Validator\Constraints\UniqueRoleValidator;

class UniqueRoleValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $em;

    private $repository;
    
    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMock(
            'Doctrine\Common\Persistence\ObjectRepository',
            array('find', 'findAll', 'findBy', 'findOneBy', 'findOneByRole', 'getClassName')
        );

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));
    }
    
    /**
     * Test method validate is working
     */
    public function testValidate()
    {
        $context = $this->getMockBuilder('Symfony\Component\Validator\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())
            ->method('addViolation');
        
        $constraint = $this->getMock('Oro\Bundle\UserBundle\Validator\Constraints\UniqueRole');
        $validator = new UniqueRoleValidator($this->em);
        $validator->initialize($context);
        $role = new Role('User');
        
        $validator->validate($role, $constraint);
    }
}