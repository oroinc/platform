<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\NameFormatType;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NameFormatTypeTest extends TestCase
{
    public function testFormType(): void
    {
        $nameFormatter = $this->createMock(NameFormatter::class);
        $format = '%test%';
        $nameFormatter->expects($this->once())
            ->method('getNameFormat')
            ->willReturn($format);
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data' => $format]);

        $type = new NameFormatType($nameFormatter);
        $this->assertEquals(TextType::class, $type->getParent());
        $this->assertEquals('oro_name_format', $type->getName());
        $type->configureOptions($resolver);
    }
}
