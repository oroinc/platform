<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_0\OroTestFrameworkBundle;

class OroTestFrameworkBundleInstaller implements Installation, ActivityExtensionAwareInterface
{
    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroTestFrameworkBundle::addTestActivityTable($schema);
        OroTestFrameworkBundle::addTestActivityTargetTable($schema);
        OroTestFrameworkBundle::addOrganizationFields($schema);
        OroTestFrameworkBundle::addActivityAssociations($schema, $this->activityExtension);
    }
}
