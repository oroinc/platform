<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
}
