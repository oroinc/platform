<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\NameFormatType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class NameFormatTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testFormType()
    {
        $nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $format = '%test%';
        $nameFormatter->expects($this->once())
            ->method('getNameFormat')
            ->will($this->returnValue($format));
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(array('data' => $format));

        $type = new NameFormatType($nameFormatter);
        $this->assertEquals(TextType::class, $type->getParent());
        $this->assertEquals('oro_name_format', $type->getName());
        $type->configureOptions($resolver);
    }
}
