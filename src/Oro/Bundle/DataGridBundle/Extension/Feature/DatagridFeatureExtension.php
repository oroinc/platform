<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Extension\Feature;

use Oro\Bundle\DataGridBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\DatagridDisabledException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Prevents building of a datagrid that is bound to a disabled feature
 * via the "datagrids" section of "Resources/config/oro/features.yml" configuration file.
 */
class DatagridFeatureExtension extends AbstractExtension
{
    public function __construct(
        private readonly FeatureChecker $featureChecker
    ) {
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config): void
    {
        $gridName = $config->getName();
        if (!$this->featureChecker->isResourceEnabled($gridName, FeatureConfigurationExtension::RESOURCE_TYPE)) {
            throw new DatagridDisabledException(sprintf(
                'The "%s" datagrid is disabled because a feature this datagrid belongs to is disabled.',
                $gridName
            ));
        }
    }

    #[\Override]
    public function getPriority(): int
    {
        // should be executed after all other extensions
        return -300;
    }
}
