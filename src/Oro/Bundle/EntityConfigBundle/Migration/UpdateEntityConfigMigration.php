<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Symfony\Component\HttpKernel\KernelInterface;

class UpdateEntityConfigMigration implements Migration
{
    /**
     * @var ConfigDumper
     */
    protected $configDumper;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(ConfigDumper $configDumper, KernelInterface $kernel)
    {
        $this->configDumper = $configDumper;
        $this->kernel = $kernel;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateEntityConfigMigrationQuery($this->configDumper, $this->kernel));
    }
}
