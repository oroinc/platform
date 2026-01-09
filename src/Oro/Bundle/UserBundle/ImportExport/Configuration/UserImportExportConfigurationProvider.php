<?php

namespace Oro\Bundle\UserBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides import/export configuration for user entities.
 *
 * This provider defines the configuration for user import and export operations,
 * specifying the entity class and the processors to use for handling user data
 * during import/export workflows.
 */
class UserImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    #[\Override]
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => User::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_user',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_user',
        ]);
    }
}
