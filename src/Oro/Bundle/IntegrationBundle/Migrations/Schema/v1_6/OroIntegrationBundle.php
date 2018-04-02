<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtension;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration, ChangeTypeExtensionAwareInterface
{
    /**
     * @var ChangeTypeExtension
     */
    protected $changeTypeExtension;

    /**
     * {@inheritdoc}
     */
    public function setChangeTypeExtension(ChangeTypeExtension $changeTypeExtension)
    {
        $this->changeTypeExtension = $changeTypeExtension;
    }

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
            Type::INTEGER
        );
        $this->changeTypeExtension->changePrimaryKeyType(
            $schema,
            $queries,
            'oro_integration_channel_status',
            'id',
            Type::INTEGER
        );
        $this->changeTypeExtension->changePrimaryKeyType(
            $schema,
            $queries,
            'oro_integration_transport',
            'id',
            Type::INTEGER
        );
    }
}
