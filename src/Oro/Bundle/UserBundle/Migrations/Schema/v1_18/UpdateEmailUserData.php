<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Depends to the UserBundle
 *
 * Class UpdateEmailUserData
 * @package Oro\Bundle\UserBundle\Migrations\Schema\v1_18
 */
class UpdateEmailUserData implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new UpdateEmailUserQuery());
    }
}
