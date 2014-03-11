<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class RefreshExtendCacheMigrationQuery implements MigrationQuery
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
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'REFRESH EXTEND ENTITY CACHE';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Connection $connection)
    {
        if ($this->clearEntityConfigCache) {
            $this->entityConfigDumper->clearConfigCache();
        }
        $this->extendConfigDumper->updateConfig();
        $this->extendConfigDumper->dump();
    }
}
