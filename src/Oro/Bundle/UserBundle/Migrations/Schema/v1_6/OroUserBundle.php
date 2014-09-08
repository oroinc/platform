<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtension;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration, ChangeTypeExtensionAwareInterface
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
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_access_group', 'id', Type::INTEGER);
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_access_role', 'id', Type::INTEGER);
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_user_email', 'id', Type::INTEGER);
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_user_status', 'id', Type::INTEGER);
    }
}
