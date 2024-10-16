<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\ValidationGroupSequenceExtension;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validation;

class ValidationGroupSequenceExtensionTest extends FormIntegrationTestCase
{
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getTypeExtensions(),
            [
                new FormTypeValidatorExtension(Validation::createValidator()),
                new ValidationGroupSequenceExtension(),
            ]
        );
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(array $options, GroupSequence|array|null $expectedValidationGroups): void
    {
        $form = $this->factory->create(TextType::class, null, $options);

        $this->assertFormOptionEqual($expectedValidationGroups, 'validation_groups', $form);
    }

    public function buildFormDataProvider(): iterable
    {
        yield 'no validation groups' => [[], null];

        yield 'empty validation groups' => [['validation_groups' => []], null];

        yield 'not array' => [['validation_groups' => 'group1'], ['group1']];

        yield 'group sequence' => [
            ['validation_groups' => new GroupSequence(['group1', 'group2'])],
            new GroupSequence(['group1', 'group2']),
        ];

        yield 'simple validation groups' => [['validation_groups' => ['group1', 'group2']], ['group1', 'group2']];

        yield 'with nested validation groups' => [
            ['validation_groups' => [['group1', 'group2'], 'group3']],
            [new GroupSequence(['group1', 'group2']), 'group3'],
        ];

        yield 'with nested groups and with group sequence' => [
            ['validation_groups' => [['group1', 'group2'], new GroupSequence(['group3', 'group4'])]],
            [new GroupSequence(['group1', 'group2']), new GroupSequence(['group3', 'group4'])],
        ];

        $closure = static fn () => ['from_closure'];
        yield 'with closure, nested groups and with group sequence' => [
            [
                'validation_groups' => [
                    $closure,
                    ['group1', 'group2'],
                    new GroupSequence(['group3', 'group4']),
                ],
            ],
            [$closure, new GroupSequence(['group1', 'group2']), new GroupSequence(['group3', 'group4'])],
        ];
    }
}
