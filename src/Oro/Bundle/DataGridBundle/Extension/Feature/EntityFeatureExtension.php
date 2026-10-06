<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Extension\Feature;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\DatagridDisabledException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Prevents building of a datagrid when the entity declared by the "extended_entity_name" option
 * of the datagrid configuration is bound to a disabled feature via the "entities" section
 * of "Resources/config/oro/features.yml" configuration file.
 *
 * Use the "features.ignore_entity_state" datagrid option to opt out of this check.
 */
class EntityFeatureExtension extends AbstractExtension
{
    public const string IGNORE_ENTITY_STATE_PATH = '[features][ignore_entity_state]';

    private const string RESOURCE_TYPE = 'entities';

    public function __construct(
        private readonly FeatureChecker $featureChecker
    ) {
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return
            parent::isApplicable($config)
            && null !== $config->getExtendedEntityClassName()
            && !$config->offsetGetByPath(self::IGNORE_ENTITY_STATE_PATH, false);
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config): void
    {
        $entityClass = $config->getExtendedEntityClassName();
        if (!$this->featureChecker->isResourceEnabled($entityClass, self::RESOURCE_TYPE)) {
            throw new DatagridDisabledException(sprintf(
                'The "%s" datagrid is disabled because a feature the "%s" entity belongs to is disabled.',
                $config->getName(),
                $entityClass
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
