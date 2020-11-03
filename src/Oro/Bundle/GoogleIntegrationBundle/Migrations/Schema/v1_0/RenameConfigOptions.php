<?php

namespace Oro\Bundle\GoogleIntegrationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigNameQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Renames configuration options related to the Google OAuth single sign-on authentication:
 * * oro_sso.enable_google_sso -> oro_google_integration.enable_sso
 * * oro_sso.domains -> oro_google_integration.sso_domains
 */
class RenameConfigOptions implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new RenameConfigNameQuery('enable_google_sso', 'enable_sso', 'oro_sso', 'oro_google_integration')
        );
        $queries->addQuery(
            new RenameConfigNameQuery('domains', 'sso_domains', 'oro_sso', 'oro_google_integration')
        );
    }
}
