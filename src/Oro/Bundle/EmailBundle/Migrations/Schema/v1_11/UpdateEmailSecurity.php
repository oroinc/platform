<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEmailSecurity implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\EmailBundle\Entity\Email',
                'security',
                'permissions',
                'VIEW;CREATE;EDIT'
            )
        );
    }
}
