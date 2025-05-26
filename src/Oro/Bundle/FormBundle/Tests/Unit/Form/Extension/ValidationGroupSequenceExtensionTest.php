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

    public function buildFormDataProvider(): array
    {
        $closure = static fn () => ['from_closure'];

        return [
            'no validation groups' => [
                [],
                null
            ],
            'empty validation groups' => [
                ['validation_groups' => []],
                null
            ],
            'not array' => [
                ['validation_groups' => 'group1'],
                ['group1']
            ],
            'group sequence' => [
                ['validation_groups' => new GroupSequence(['group1', 'group2'])],
                new GroupSequence(['group1', 'group2'])
            ],
            'simple validation groups' => [['validation_groups' => ['group1', 'group2']], ['group1', 'group2']],
            'with nested validation groups' => [
                ['validation_groups' => [['group1', 'group2'], 'group3']],
                [new GroupSequence(['group1', 'group2']), 'group3']
            ],
            'with nested groups and with group sequence' => [
                ['validation_groups' => [['group1', 'group2'], new GroupSequence(['group3', 'group4'])]],
                [new GroupSequence(['group1', 'group2']), new GroupSequence(['group3', 'group4'])]
            ],
            'with closure, nested groups and with group sequence' => [
                ['validation_groups' => [$closure, ['group1', 'group2'], new GroupSequence(['group3', 'group4'])]],
                [$closure, new GroupSequence(['group1', 'group2']), new GroupSequence(['group3', 'group4'])]
            ]
        ];
    }
}
