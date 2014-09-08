<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroUserApiKeyAddOrganizationField($schema);
        self::oroUserApiKeyIndexes($schema);
    }

    /**
     * Adds organization_id field to oro_user_api table
     *
     * @param Schema $schema
     */
    public static function oroUserApiKeyAddOrganizationField(Schema $schema)
    {
        $table = $schema->getTable('oro_user_api');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_296B699332C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Drop unique user index, fk. Add new ones
     *
     * @param Schema $schema
     */
    public static function oroUserApiKeyIndexes(Schema $schema)
    {
        $table = $schema->getTable('oro_user_api');
        if ($table->hasIndex('UNIQ_296B6993A76ED395')) {
            $table->removeForeignKey('fk_oro_user_api_user_id');
            $table->dropIndex('UNIQ_296B6993A76ED395');
        }

        $table->addIndex(['user_id'], 'IDX_296B6993A76ED395', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
