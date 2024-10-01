<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveDictionaryGroupForUser implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new RemoveDictionaryGroupForUserQuery());
    }
}
