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
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $roles);
        $this->assertCount(0, $roles);
    }

    /**
     * @test
     */
    public function shouldReturnRoles()
    {
        $group = new Group();
        $this->writeAttribute($group, 'roles', $roles = new ArrayCollection(['role1']));
        $this->assertSame($roles, $group->getRoles());
    }
}
