<?php

namespace Oro\Bundle\WindowsBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWindowsBundle implements Migration, DatabasePlatformAwareInterface
{
    /** @var AbstractPlatform */
    protected $platform;

    /** {@inheritdoc} */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /** {@inheritdoc} */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_windows_state');
        $column = $table->getColumn('data');

        if ($this->platform instanceof PostgreSQL92Platform) {
            $queries->addPreQuery(
                'ALTER TABLE oro_windows_state ALTER COLUMN data TYPE JSON USING data::JSON'
            );
        } else {
            $column->setType(Type::getType(Type::JSON_ARRAY));
        }

        $column->setOptions(['comment' => '(DC2Type:json_array)']);
    }
}
