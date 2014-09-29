<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;

class UpdateEntityLabel implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\TagBundle\Entity\Tag',
                'entity',
                'label',
                'oro.tag.entity_label'
            )
        );
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\TagBundle\Entity\Tag',
                'entity',
                'plural_label',
                'oro.tag.entity_plural_label'
            )
        );
    }
}
