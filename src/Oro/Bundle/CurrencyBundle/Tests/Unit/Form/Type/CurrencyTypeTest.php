<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType as BaseCurrencyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrencyType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new CurrencyType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'restrict' => false
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseCurrencyType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_currency', $this->formType->getName());
    }
}
