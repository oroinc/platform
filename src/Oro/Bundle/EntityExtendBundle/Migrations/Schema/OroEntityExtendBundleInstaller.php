<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_0\RenameExtendTablesAndColumns;
use Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_1\OroEntityExtendBundle as OroEntityExtendBundle11;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityExtendBundleInstaller extends RenameExtendTablesAndColumns implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_8';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // rename should not be performed during a fresh installation
        if ($this->container->hasParameter('installed') && $this->container->getParameter('installed')) {
            parent::up($schema, $queries);
        }

        OroEntityExtendBundle11::oroEnumValueTransTable($schema);
    }
}
