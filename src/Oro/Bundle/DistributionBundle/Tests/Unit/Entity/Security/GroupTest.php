<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity\Security;


use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DistributionBundle\Entity\Security\Group;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;

class GroupTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionHelperTrait;

    /**
     * @test
     */
    public function couldBeConstructedWithoutArgs()
    {
        new Group();
    }

    /**
     * @test
     */
    public function shouldReturnEmptyRolesByDefault()
    {
        $group = new Group();
        $roles = $group->getRoles();
        $this->assertInternalType('array', $roles);
        $this->assertCount(0, $roles);
    }

    /**
     * @test
     */
    public function shouldReturnRoles()
    {
        $group = new Group();
        $roles = ['role1', 'role2'];
        $this->writeAttribute($group, 'roles', new ArrayCollection($roles));
        $this->assertEquals($roles, $group->getRoles());
    }
}
