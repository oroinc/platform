<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtensionAwareInterface;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration, ChangeTypeExtensionAwareInterface
{
    use ChangeTypeExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeTypeExtension->changePrimaryKeyType(
            $schema,
            $queries,
            'oro_integration_channel',
            'id',
            Types::INTEGER
        );
        $this->changeTypeExtension->changePrimaryKeyType(
            $schema,
            $queries,
            'oro_integration_channel_status',
            'id',
            Types::INTEGER
        );
        $this->changeTypeExtension->changePrimaryKeyType(
            $schema,
            $queries,
            'oro_integration_transport',
            'id',
            Types::INTEGER
        );
    }
}
