<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Change token field type (MySql error: 1118 Row size too large).
 */
class ChangeTokenFieldType implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->changeEmailAccessTokenFieldType($schema);
        $this->changeEmailRefreshTokenFieldType($schema);
    }

    private function changeEmailAccessTokenFieldType(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        if ($table->getColumn('access_token')->getType()->getName() !== Types::TEXT) {
            $table->changeColumn('access_token', ['type' => TextType::getType(Types::TEXT), 'length' => 8192]);
        }
    }

    private function changeEmailRefreshTokenFieldType(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        if ($table->getColumn('refresh_token')->getType()->getName() !== Types::TEXT) {
            $table->changeColumn('refresh_token', ['type' => TextType::getType(Types::TEXT), 'length' => 8192]);
        }
    }
}
