<?php

namespace Oro\Bundle\ActivityListBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ActivityListMigration implements Migration
{
    /**  @var ActivityListChainProvider */
    protected $provider;

    /** @var ActivityListExtension */
    protected $activityListExtension;

    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * @param ActivityListChainProvider       $provider
     * @param ActivityListExtension           $activityListExtension
     * @param EntityMetadataHelper            $metadataHelper
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        ActivityListChainProvider $provider,
        ActivityListExtension $activityListExtension,
        EntityMetadataHelper $metadataHelper,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->provider              = $provider;
        $this->activityListExtension = $activityListExtension;
        $this->metadataHelper        = $metadataHelper;
        $this->nameGenerator         = $nameGenerator;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new ActivityListMigrationQuery(
                    $schema,
                    $this->provider,
                    $this->activityListExtension,
                    $this->metadataHelper,
                    $this->nameGenerator
                )
            );
        }
    }
}
