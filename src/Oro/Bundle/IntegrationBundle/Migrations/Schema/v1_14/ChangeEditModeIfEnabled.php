<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeEditModeIfEnabled implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $allow = Channel::EDIT_MODE_ALLOW;

        $queries->addQuery(sprintf('UPDATE oro_integration_channel SET edit_mode=%d WHERE enabled=true', $allow));
    }
}
