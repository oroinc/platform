<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds several extended attributes to TestActivityTarget entity to use in functional tests.
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData
 */
class AddAttributesToTestActivityTargetMigration implements Migration
{
    public const SYSTEM_ATTRIBUTE_1 = 'system_attribute_1';
    public const SYSTEM_ATTRIBUTE_2 = 'system_attribute_2';
    public const REGULAR_ATTRIBUTE_1 = 'regular_attribute_1';
    public const REGULAR_ATTRIBUTE_2 = 'regular_attribute_2';
    public const BOOL_ATTRIBUTE_1 = 'bool_attribute_1';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('test_activity_target');
        $this->addAttribute($table, self::SYSTEM_ATTRIBUTE_1, ExtendScope::OWNER_SYSTEM);
        $this->addAttribute($table, self::SYSTEM_ATTRIBUTE_2, ExtendScope::OWNER_SYSTEM);
        $this->addAttribute($table, self::REGULAR_ATTRIBUTE_1, ExtendScope::OWNER_CUSTOM);
        $this->addAttribute($table, self::REGULAR_ATTRIBUTE_2, ExtendScope::OWNER_CUSTOM);
        $this->addAttribute($table, self::BOOL_ATTRIBUTE_1, ExtendScope::OWNER_CUSTOM, 'boolean');
    }

    private function addAttribute(Table $table, string $attributeName, string $owner, string $type = 'string'): void
    {
        if ($table->hasColumn($attributeName)) {
            return;
        }

        $table->addColumn(
            $attributeName,
            $type,
            [
                OroOptions::KEY => [
                    'extend'    => [
                        'owner' => $owner
                    ],
                    'attribute' => [
                        'is_attribute' => true
                    ]
                ]
            ]
        );
    }
}
