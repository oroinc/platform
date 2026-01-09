<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_12;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates the length of user agent, as it could be more than 255 symbols
 */
class UpdateUserAgentLength implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_user_login');
        $column =  $table->getColumn('user_agent');
        // Column type already changed
        if ($column->getType()->getName() === Types::TEXT) {
            return;
        }

        //use text field cause user-agent has no restrictions for length
        $table->modifyColumn('user_agent', ['type' => Type::getType(Types::TEXT), 'notnull' => false, 'default' => '']);
    }
}
