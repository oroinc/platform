<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\LocaleBundle\Form\Type\CountryType;

class CountryTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CountryType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new CountryType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => array_flip(Intl::getRegionBundle()->getCountryNames('en')),
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('country', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_country', $this->formType->getName());
    }
}
