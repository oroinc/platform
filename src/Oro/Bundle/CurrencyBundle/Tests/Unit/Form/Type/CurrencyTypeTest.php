<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencyType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType as BaseCurrencyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyTypeTest extends TestCase
{
    private CurrencyType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new CurrencyType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'restrict' => false
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(BaseCurrencyType::class, $this->formType->getParent());
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_currency', $this->formType->getName());
    }
}
