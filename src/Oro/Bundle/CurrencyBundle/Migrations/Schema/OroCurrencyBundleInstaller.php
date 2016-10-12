<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\CurrencyBundle\Migrations\Schema\v1_0\CurrencyConfigOrganizationMigration;

class OroConfigBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->container->hasParameter('installed') && $this->container->getParameter('installed')) {
            CurrencyConfigOrganizationMigration::migrateOrganizationCurrencyConfig($queries);
        }
    }
}
