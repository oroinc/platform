<?php

namespace Oro\Bundle\GoogleIntegrationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigNameQuery;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroGoogleIntegrationInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateUserEntity($schema);
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $this->renameGoogleSsoConfigOptions($queries);
        }
    }

    private function updateUserEntity(Schema $schema): void
    {
        $userTable = $schema->getTable('oro_user');
        if (!$userTable->hasColumn('googleId')) {
            $userTable->addColumn('googleId', 'string', [
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_SYSTEM]
                ],
                'notnull'     => false
            ]);
        }
    }

    private function renameGoogleSsoConfigOptions(QueryBag $queries): void
    {
        $queries->addQuery(
            new RenameConfigNameQuery('enable_google_sso', 'enable_sso', 'oro_sso', 'oro_google_integration')
        );
        $queries->addQuery(
            new RenameConfigNameQuery('domains', 'sso_domains', 'oro_sso', 'oro_google_integration')
        );
    }
}
