<?php

namespace Oro\Bundle\UserBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\UserBundle\Entity\Role;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class RoleTest extends \PHPUnit_Framework_TestCase
{
    public function testRole()
    {
        $role = $this->getRole();

        $this->assertEmpty($role->getId());
        $this->assertEmpty($role->getRole());

        $role->setRole('foo');

        $this->assertEquals('ROLE_FOO', $role->getRole());
    }

    public function testLabel()
    {
        $role  = $this->getRole();
        $label = 'Test role';

        $this->assertEmpty($role->getLabel());

        $role->setLabel($label);

        $this->assertEquals($label, $role->getLabel());
    }

    protected function setUp()
    {
        $this->role = new Role();
    }

    /**
     * @return Role
     */
    protected function getRole()
    {
        return $this->role;
    }

    public function testOwners()
    {
        $entity = $this->getRole();
        $businessUnit = new BusinessUnit();

        $this->assertEmpty($entity->getOwner());

        $entity->setOwner($businessUnit);

        $this->assertEquals($businessUnit, $entity->getOwner());
    }
    
    /**
     * Test prePersist role that to generate new value of "role" field
     */
    public function testCallbacks()
    {
        $role = $this->getRole();
        
        $this->assertEmpty($role->getId());
        $this->assertEmpty($role->getRole());
        
        $role->beforeSave($this->getEvent($role));
        
        $this->assertNotEmpty($role->getRole());
    }
    
    /**
     * Prepare event object for test callbacks
     * 
     * @param Role $entity
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEvent($entity)
    {
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMock(
            'Doctrine\Common\Persistence\ObjectRepository',
            array('find', 'findAll', 'findBy', 'findOneBy', 'findOneByRole', 'getClassName')
        );
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));
        
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $event->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        return $event;
    }
}
