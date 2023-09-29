<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Utils;

use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Contains handy functions for working with validation groups.
 */
class ValidationGroupUtils
{
    /**
     * Transforms nested array of validation group names into {@see GroupSequence}, for example:
     *  1. [['Default', 'custom_group_name'], 'another_group'] will be become:
     *      [new GroupSequence(['Default', 'custom_group_name']), 'another_group']
     *  2. ['Default', 'custom_group_name', 'another_group'] will become:
     *      ['Default', 'custom_group_name', 'another_group']
     *
     * Substitutes placeholders with values in validation groups.
     *
     * @param array<string|string[]|GroupSequence> $validationGroups
     * @param array<string,string> $placeholders
     *
     * @return array<string|GroupSequence>
     */
    public static function resolveValidationGroups(array $validationGroups, array $placeholders = []): array
    {
        $processedGroups = [];
        foreach ($validationGroups as $groupOrGroups) {
            if ($groupOrGroups instanceof GroupSequence) {
                $groupOrGroups = $groupOrGroups->groups;
            }

            if (is_array($groupOrGroups)) {
                $groupOrGroups = new GroupSequence(self::resolveValidationGroups($groupOrGroups, $placeholders));
            } elseif ($placeholders) {
                $groupOrGroups = strtr($groupOrGroups, $placeholders);
            }

            $processedGroups[] = $groupOrGroups;
        }

        return $processedGroups;
    }
}
