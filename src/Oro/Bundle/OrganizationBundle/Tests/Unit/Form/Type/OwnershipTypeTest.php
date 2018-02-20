<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;

class OwnershipTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OwnershipType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new OwnershipType();
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testConfigureOptions()
    {
        $optionResolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');

        $optionResolver->expects($this->once())
            ->method('setDefaults')
            ->with(array('choices' => $this->type->getOwnershipsArray()));
        $this->type->configureOptions($optionResolver);
    }

    public function testGetName()
    {
        $this->assertEquals(OwnershipType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }
}
