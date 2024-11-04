<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddFirstNameLastNameIndex implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_user')
            ->addIndex(['first_name', 'last_name'], 'user_first_name_last_name_idx');
    }
}
