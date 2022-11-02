<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\DraftBundle\Form\Extension\ExtendFieldTypeExtension;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtendFieldTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigureOptions(): void
    {
        $extension = new ExtendFieldTypeExtension(['first_type']);
        $resolver = new OptionsResolver();
        $extension->configureOptions($resolver);
        $options = $resolver->resolve(['class_name' => DraftableEntityStub::class, 'excludeTypes' => ['second_type']]);
        $this->assertEquals(
            ['class_name' => DraftableEntityStub::class, 'excludeTypes' => ['second_type', 'first_type']],
            $options
        );
    }

    /**
     * @dataProvider configureOptionsExceptionDataProvider
     */
    public function testConfigureOptionsExceptions(string $exceptionMessage, array $options): void
    {
        $extension = new ExtendFieldTypeExtension();
        $resolver = new OptionsResolver();
        $this->expectExceptionMessage($exceptionMessage);
        $extension->configureOptions($resolver);
        $resolver->resolve($options);
    }

    public function configureOptionsExceptionDataProvider(): array
    {
        return [
            'Missing options: class_name, excludeTypes' => [
                'exceptionMessage' => 'The required options "class_name", "excludeTypes" are missing.',
                'options' => []
            ],
            'Missing options: class_name' => [
                'exceptionMessage' => 'The required option "excludeTypes" is missing.',
                'options' => ['class_name' => DraftableEntityStub::class]
            ],
            'Missing options: excludeTypes' => [
                'exceptionMessage' => 'The required option "class_name" is missing.',
                'options' => ['excludeTypes' => DraftableEntityStub::class]
            ]
        ];
    }
}
