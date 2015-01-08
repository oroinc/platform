<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class OrganizationTest extends \PHPUnit_Framework_TestCase
{
    /** @var Organization */
    protected $organization;

    protected function setUp()
    {
        $this->organization = new Organization();
    }

    public function testName()
    {
        $name = 'testName';
        $this->assertNull($this->organization->getName());
        $this->organization->setName($name);
        $this->assertEquals($name, $this->organization->getName());
        $this->assertEquals($name, (string)$this->organization);
    }

    public function testId()
    {
        $this->assertNull($this->organization->getId());
    }

    /**
     * @dataProvider provider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Organization();

        call_user_func_array([$obj, 'set' . ucfirst($property)], [$value]);

        $this->assertEquals(
            $value,
            call_user_func_array(
                [
                    $obj,
                    method_exists($obj, 'get' . ucfirst($property))
                        ? 'get' . ucfirst($property)
                        : 'is' . ucfirst($property)
                ],
                []
            )
        );
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provider()
    {
        return [
            ['name', 'test'],
            ['description', 'test'],
            ['enabled', 1],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['businessUnits', new ArrayCollection([new BusinessUnit()])],
            ['users', new ArrayCollection([new User()])]
        ];
    }

    public function testAddRemoveUser()
    {
        $org = new Organization();

        $user = new User();
        $user->setId(uniqid());

        $this->assertFalse($org->hasUser($user));

        $org->addUser($user);

        $users = $org->getUsers()->toArray();
        $this->assertCount(1, $users);
        $this->assertTrue($org->hasUser($user));
        $this->assertEquals($user, reset($users));

        $org->removeUser($user);

        $this->assertFalse($org->hasUser($user));
    }
}
