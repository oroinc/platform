<?php

namespace Oro\Bundle\DataAuditBundle\Datagrid;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Provides human-readable EntityType column value and EntityTypes filter list for audit data grid.
 */
class EntityTypeProvider
{
    private EntityClassNameProviderInterface $entityClassNameProvider;
    private AuditConfigProvider $configProvider;
    private FeatureChecker $featureChecker;

    public function __construct(
        EntityClassNameProviderInterface $entityClassNameProvider,
        AuditConfigProvider $configProvider,
        FeatureChecker $featureChecker
    ) {
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->configProvider = $configProvider;
        $this->featureChecker = $featureChecker;
    }

    public function getEntityType(): callable|\Closure
    {
        return function (ResultRecord $record) {
            return $this->entityClassNameProvider->getEntityClassName(
                $record->getValue('objectClass')
            );
        };
    }

    /**
     * @return array [entity class => entity type, ...]
     */
    public function getEntityTypes(): array
    {
        $result = [];
        $classNames = $this->configProvider->getAllAuditableEntities();
        foreach ($classNames as $className) {
            if (!$this->featureChecker->isResourceEnabled($className, 'entities')) {
                continue;
            }

            $label = $this->entityClassNameProvider->getEntityClassName($className);
            if ($label) {
                $result[$label] = $className;
            }
        }
        asort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }
}
