<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var CountryType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new CountryType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2TranslatableEntityType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_country', $this->type->getName());
    }
}
