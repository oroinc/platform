<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\LocaleBundle\Form\Type\LocaleType;

class LocaleTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocaleType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new LocaleType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => array_flip(Intl::getLocaleBundle()->getLocaleNames('en')),
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('locale', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale', $this->formType->getName());
    }
}
