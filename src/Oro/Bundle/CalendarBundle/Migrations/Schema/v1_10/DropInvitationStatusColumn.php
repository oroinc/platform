<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropInvitationStatusColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_event');
        $table->dropColumn('invitation_status');
    }
}
