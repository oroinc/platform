<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Marks some extended attributes added in AddAttributesToTestActivityTargetMigration as to be deleted
 * and adds a new attribute with "new" state.
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment\AddAttributesToTestActivityTargetMigration
 * @see \Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope::STATE_DELETE
 * @see \Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope::STATE_NEW
 */
class UpdateAttributesForTestActivityTargetMigration implements Migration
{
    const NOT_USED_ATTRIBUTE = 'not_used_attribute';

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateAttributesForTestActivityTargetQuery($this->configManager));
    }
}
