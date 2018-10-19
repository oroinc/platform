<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Oro\Bundle\TagBundle\Form\Type\TaxonomySelectType;

class UpdateTaxonomyFormType implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                Taxonomy::class,
                'form',
                'form_type',
                TaxonomySelectType::class,
                'oro_taxonomy_select'
            )
        );
    }
}
