<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtensionAwareInterface;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration, ChangeTypeExtensionAwareInterface
{
    use ChangeTypeExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_access_group', 'id', Types::INTEGER);
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_access_role', 'id', Types::INTEGER);
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_user_email', 'id', Types::INTEGER);
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_user_status', 'id', Types::INTEGER);
    }
}
