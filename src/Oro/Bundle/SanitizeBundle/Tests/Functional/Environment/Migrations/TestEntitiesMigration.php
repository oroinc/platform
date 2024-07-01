<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements
    Migration,
    ExtendExtensionAwareInterface,
    SerializedFieldsExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;
    use SerializedFieldsExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('test_sanitizable_entity')) {
            return;
        }

        $this->createTestSanitizable($schema);
        $this->addSerializedFieldsToTestSanitizable($schema);
    }

    private function createTestSanitizable(Schema $schema): void
    {
        $table = $schema->createTable('test_sanitizable_entity');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('first_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('middle_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('last_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('birthday', 'datetime', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('emailunguessable', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('phone', 'string', [
            'length' => 255,
            'notnull' => false,
            'oro_options' => [
                'sanitize' => [
                    'rule' => 'digits_mask',
                    'rule_options' => ['mask' => '1 800 XXX-XXX-XXXX']
                ]
            ]
        ]);
        $table->addColumn('phone_second', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('secret', 'string', [
            'length' => 255,
            'comment' => '(DC2Type:crypted_string)'
        ]);
        $table->addColumn('text_secret', 'text', [
            'comment' => '(DC2Type:crypted_text)'
        ]);
        $table->addColumn('state_data', 'array', ['comment' => '(DC2Type:array)']);

        $table->setPrimaryKey(['id']);
    }

    private function addSerializedFieldsToTestSanitizable(Schema $schema): void
    {
        $table = $schema->getTable('test_sanitizable_entity');
        $commonOptions = [
            'extend' => [
                'is_extend' => true,
                'owner' => ExtendScope::OWNER_CUSTOM,
            ],
            'attribute' => [
                'is_attribute' => true
            ]
        ];

        $this->serializedFieldsExtension->addSerializedField($table, 'email_third', 'string', $commonOptions);
        $this->serializedFieldsExtension->addSerializedField($table, 'email_wrong_type', 'integer', $commonOptions);
        $this->serializedFieldsExtension->addSerializedField($table, 'first_custom_field', 'string', $commonOptions);
        $this->serializedFieldsExtension->addSerializedField(
            $table,
            'custom_event_date',
            'datetime',
            array_merge($commonOptions, ['sanitize' => ['rule' => 'date']])
        );
        $this->serializedFieldsExtension->addSerializedField(
            $table,
            'phone_third',
            'string',
            array_merge($commonOptions, ['sanitize' => ['rule' => 'generic_phone']])
        );
    }
}
