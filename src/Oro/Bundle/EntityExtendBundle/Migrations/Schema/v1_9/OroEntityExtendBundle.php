<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroEntityExtendBundle implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** {@inheritdoc} */
    public function up(Schema $schema, QueryBag $queries)
    {
        $query = "UPDATE oro_entity_config_field SET mode='%s' WHERE type='%s' and mode='default'";
        $queries->addQuery(sprintf($query, ConfigModel::MODE_READONLY, RelationType::ONE_TO_ONE));
        $queries->addQuery(sprintf($query, ConfigModel::MODE_READONLY, RelationType::MANY_TO_ONE));
        $queries->addQuery(sprintf($query, ConfigModel::MODE_READONLY, RelationType::ONE_TO_MANY));
        $queries->addQuery(sprintf($query, ConfigModel::MODE_READONLY, RelationType::MANY_TO_MANY));
    }
}
