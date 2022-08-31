<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Change organization relation onDelete behavior from SET NULL to CASCADE
 */
class SetOwnerForEmailOrganizationOnDeleteCascade implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateOrganizationConstraint($schema);
    }

    public static function updateOrganizationConstraint(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_user');

        if ($table->hasForeignKey('FK_91F5CFF632C8A3DE')) {
            $table->removeForeignKey('FK_91F5CFF632C8A3DE');
        }

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_91F5CFF632C8A3DE'
        );
    }
}
