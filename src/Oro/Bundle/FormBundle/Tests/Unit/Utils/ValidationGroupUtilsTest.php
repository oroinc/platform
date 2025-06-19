<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Unit\Utils;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GroupSequence;

class ValidationGroupUtilsTest extends TestCase
{
    /**
     * @dataProvider resolveValidationGroupsDataProvider
     */
    public function testResolveValidationGroups(
        array $validationGroups,
        array $placeholders,
        array $expected
    ): void {
        self::assertEquals(
            $expected,
            ValidationGroupUtils::resolveValidationGroups($validationGroups, $placeholders)
        );
    }

    public function resolveValidationGroupsDataProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
                []
            ],
            'not empty groups' => [
                ['group1', 'group2'],
                [],
                ['group1', 'group2']
            ],
            'not empty groups, without placeholders' => [
                ['group1%placeholder%', 'group2'],
                [],
                ['group1%placeholder%', 'group2']
            ],
            'not empty groups with placeholder, with placeholders' => [
                ['group1%placeholder%', 'group2'],
                ['%placeholder%' => '_user'],
                ['group1_user', 'group2']
            ],
            'not empty groups with group sequence, with placeholders' => [
                [['group1%placeholder%', 'group2']],
                ['%placeholder%' => '_user'],
                [new GroupSequence(['group1_user', 'group2'])]
            ]
        ];
    }
}
