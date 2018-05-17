<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;

class UpdateUserFormType implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                User::class,
                'form',
                'form_type',
                UserSelectType::class,
                'oro_user_select'
            )
        );
    }
}
