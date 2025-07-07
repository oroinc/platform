<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema\v6_1_3_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateNumberSequence implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_number_sequence')) {
            $table = $schema->createTable('oro_number_sequence');
            $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
            $table->setPrimaryKey(['id']);

            $table->addColumn('sequence_type', Types::STRING, ['length' => 255]);
            $table->addColumn('discriminator_type', Types::STRING, ['length' => 255]);
            $table->addColumn('discriminator_value', Types::STRING, ['length' => 255]);
            $table->addColumn('number', Types::INTEGER, []);
            $table->addColumn('created_at', Types::DATETIME_MUTABLE, ['comment' => '(DC2Type:datetime)']);
            $table->addColumn('updated_at', Types::DATETIME_MUTABLE, ['comment' => '(DC2Type:datetime)']);

            $table->addUniqueIndex(['sequence_type', 'discriminator_type', 'discriminator_value'], 'oro_sequence_uidx');
        }
    }
}
