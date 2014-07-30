<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_0\OroIntegrationBundle as v10;
use Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_2\OroIntegrationBundle as v12;
use Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_3\OroIntegrationBundle as v13;
use Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_4\OroIntegrationBundle as v14;
use Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_5\OroIntegrationBundle as v15;

class OroIntegrationBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        v10::createTransportTable($schema);

        v10::createChannelTable($schema);
        v12::modifyChannelTable($schema);
        v13::modifyChannelTable($schema);
        v14::modifyChannelTable($schema);

        v10::createChannelStatusTable($schema);
        v15::modifyChannelStatusTable($schema);
    }
}
