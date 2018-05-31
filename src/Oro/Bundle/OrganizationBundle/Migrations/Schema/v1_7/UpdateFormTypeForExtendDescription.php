<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class UpdateFormTypeForExtendDescription implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                BusinessUnit::class,
                'extend_description',
                'form',
                'type',
                OroResizeableRichTextType::class,
                'oro_resizeable_rich_text'
            )
        );
    }
}
