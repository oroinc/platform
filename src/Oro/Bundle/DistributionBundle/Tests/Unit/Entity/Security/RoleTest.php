<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity\Security;


use Oro\Bundle\DistributionBundle\Entity\Security\Role;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;

class RoleTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionHelperTrait;

    /**
     * @test
     */
    public function couldBeConstructedWithoutArgs()
    {
        new Role();
    }

    /**
     * @test
     */
    public function shouldReturnEmptyStringAsRoleByDefault()
    {
        $role = new Role();
        $this->assertEquals('', $role->getRole());
    }

    /**
     * @test
     */
    public function shouldReturnRole()
    {
        $role = new Role();
        $this->writeAttribute($role, 'role', $name = uniqid());
        $this->assertSame($name, $role->getRole());
    }

    /**
     * @test
     */
    public function couldBeConvertedToStringAccordingRole()
    {
        $role = new Role();
        $this->writeAttribute($role, 'role', $name = uniqid());

        $this->assertEquals($name, (string) $role);
    }
}
