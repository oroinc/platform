<?php

namespace Oro\Bundle\SSOBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSSOBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        // this installer exists to prevent issues with further version of this installer
        // that can happen because creation of googleId field for User entity was moved
        // from this installer to OroGoogleIntegrationBundle installer
        // remove this comment when v1_1 of this installer will be created
    }
}
