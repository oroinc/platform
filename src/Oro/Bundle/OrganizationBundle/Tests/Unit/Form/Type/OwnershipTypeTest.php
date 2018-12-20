<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OwnershipTypeTest extends \PHPUnit\Framework\TestCase
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
            ->with([
                'choices' => array_flip($this->type->getOwnershipsArray()),
            ]);
        $this->type->configureOptions($optionResolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }
}
