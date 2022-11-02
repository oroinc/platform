<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\SecurityBundle\Form\Extension\AutocompletePasswordTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutocompletePasswordTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AutocompletePasswordTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new AutocompletePasswordTypeExtension();
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([PasswordType::class], AutocompletePasswordTypeExtension::getExtendedTypes());
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $options, array $expected): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined('sample_option');
        $resolver->setDefault('attr', []);

        $this->extension->configureOptions($resolver);

        self::assertEquals($expected, $resolver->resolve($options));
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'options' => [],
                'expected' => ['attr' => ['autocomplete' => 'off']],
            ],
            [
                'options' => ['sample_option' => 'sample_value'],
                'expected' => ['sample_option' => 'sample_value', 'attr' => ['autocomplete' => 'off']],
            ],
            [
                'options' => ['attr' => ['sample_attr' => 'sample_value']],
                'expected' => ['attr' => ['sample_attr' => 'sample_value', 'autocomplete' => 'off']],
            ],
            [
                'options' => ['attr' => ['autocomplete' => 'new-password']],
                'expected' => ['attr' => ['autocomplete' => 'new-password']],
            ],
        ];
    }
}
