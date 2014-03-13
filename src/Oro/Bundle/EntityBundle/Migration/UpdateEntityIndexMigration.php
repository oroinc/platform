<?php

namespace Oro\Bundle\EntityBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEntityIndexMigration implements Migration
{
    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        ConfigProvider $extendConfigProvider
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->nameGenerator        = new ExtendDbIdentifierNameGenerator();
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityIndexMigrationQuery(
                $this->entityMetadataHelper,
                $this->extendConfigProvider,
                $this->nameGenerator,
                $schema,
                $queries
            )
        );
    }
}
