<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnershipTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var OwnershipType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new OwnershipType();
    }

    public function testConfigureOptions()
    {
        $optionResolver = $this->createMock(OptionsResolver::class);

        $optionResolver->expects($this->once())
            ->method('setDefaults')
            ->with(['choices' => array_flip($this->type->getOwnershipsArray())]);
        $this->type->configureOptions($optionResolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }
}
