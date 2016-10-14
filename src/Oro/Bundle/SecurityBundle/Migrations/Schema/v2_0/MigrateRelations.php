<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateRelations implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_security_permission_entity',
            'name',
            'OroCRM',
            'Oro'
        ));

        $renames = [
            ['OroB2B\Bundle\AccountBundle', 'OroB2B\Bundle\CustomerBundle'],
            ['OroB2BAccountBundle', 'OroB2BCustomerBundle'],
            ['OroPro\Bundle\SecurityBundle\Migrations\Data\ORM\SetShareGridConfig',
                'Oro\Bundle\SecurityProBundle\Migrations\Data\ORM\SetShareGridConfig'],
            ['OroCRMPro\Bundle\SecurityBundle\Migrations\Data\ORM\SetShareGridConfig',
                'Oro\Bundle\SecurityCRMProBundle\Migrations\Data\ORM\SetShareGridConfig'],
            ['OroProOrganizationBundle', 'OroOrganizationProBundle'],
            ['OroProSecurityBundle', 'OroSecurityProBundle'],
            ['OroProUserBundle', 'OroUserProBundle'],
            ['OroCRMPro', 'Oro'],
            ['OroCRM', 'Oro'],
            ['OroPro', 'Oro'],
            ['orocrm_report_', 'oro_reportcrm_'],
            ['oropro_organization_', 'oro_organizationpro_'],
            ['orocrmpro_outlook_integration', 'oro_outlook_integration'],
            ['orocrm', 'oro']
        ];

        foreach ($renames as $rename) {
            $queries->addQuery(
                new UpdateTableFieldQuery(
                    'acl_classes',
                    'class_type',
                    $rename[0],
                    $rename[1]
                )
            );
        }
    }
}
