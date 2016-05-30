<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\LocaleBundle\Form\Type\CurrencyType;

class CurrencyTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencyType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new CurrencyType();
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => Intl::getCurrencyBundle()->getCurrencyNames('en'),
                'restrict' => false
            ]);
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with('restrict');
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('restrict', 'bool');

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('currency', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_currency', $this->formType->getName());
    }
}
