<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryTypeTest extends TestCase
{
    private CountryType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new CountryType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(Select2TranslatableEntityType::class, $this->type->getParent());
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_country', $this->type->getName());
    }
}
