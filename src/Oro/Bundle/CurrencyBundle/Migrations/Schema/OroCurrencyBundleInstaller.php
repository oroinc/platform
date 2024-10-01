<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigNameQuery;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroCurrencyBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $this->migrateOrganizationCurrencyConfig($queries);
        }
    }

    private function migrateOrganizationCurrencyConfig(QueryBag $queries): void
    {
        $queries->addPreQuery(
            new RenameConfigNameQuery('currency', 'default_currency', 'oro_locale', 'oro_currency')
        );
    }
}
