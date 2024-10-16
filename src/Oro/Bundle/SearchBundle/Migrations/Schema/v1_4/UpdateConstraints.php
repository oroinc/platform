<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateConstraints implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->changeConstraintDecimalFk($schema);
        $this->changeConstraintIntegerFk($schema);
        $this->changeConstraintDatetimeFk($schema);
    }

    private function changeConstraintDecimalFk(Schema $schema): void
    {
        $table = $schema->getTable('oro_search_index_decimal');

        if ($table->getForeignKey('FK_E0B9BB33126F525E')) {
            $table->removeForeignKey('FK_E0B9BB33126F525E');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }

    private function changeConstraintIntegerFk(Schema $schema): void
    {
        $table = $schema->getTable('oro_search_index_integer');
        if ($table->getForeignKey('FK_E04BA3AB126F525E')) {
            $table->removeForeignKey('FK_E04BA3AB126F525E');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }

    private function changeConstraintDatetimeFk(Schema $schema): void
    {
        $table = $schema->getTable('oro_search_index_datetime');
        if ($table->getForeignKey('FK_459F212A126F525E')) {
            $table->removeForeignKey('FK_459F212A126F525E');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_search_item'),
                ['item_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }
}
