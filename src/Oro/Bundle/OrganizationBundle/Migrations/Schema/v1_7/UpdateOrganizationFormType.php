<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;

class UpdateOrganizationFormType implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                Organization::class,
                'form',
                'form_type',
                OrganizationSelectType::class,
                'oro_organization_select'
            )
        );
    }
}
