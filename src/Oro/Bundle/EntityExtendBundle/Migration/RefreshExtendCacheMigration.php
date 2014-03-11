<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class RefreshExtendCacheMigration implements Migration
{
    /**
     * @var ExtendConfigDumper
     */
    protected $extendConfigDumper;

    /**
     * @var ConfigDumper
     */
    protected $entityConfigDumper;

    /**
     * @var bool
     */
    protected $clearEntityConfigCache;

    /**
     * @param ExtendConfigDumper $extendConfigDumper
     * @param ConfigDumper       $entityConfigDumper
     * @param bool               $clearEntityConfigCache
     */
    public function __construct(
        ExtendConfigDumper $extendConfigDumper,
        ConfigDumper $entityConfigDumper = null,
        $clearEntityConfigCache = false
    ) {
        $this->extendConfigDumper     = $extendConfigDumper;
        $this->entityConfigDumper     = $entityConfigDumper;
        $this->clearEntityConfigCache = $clearEntityConfigCache;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new RefreshExtendCacheMigrationQuery(
                    $this->extendConfigDumper,
                    $this->entityConfigDumper,
                    $this->clearEntityConfigCache
                )
            );
        }
    }
}
