<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateIntegrationChannelStatusJsonArrayQuery());
    }
}
