<?php

namespace Oro\Bundle\DataAuditBundle\Datagrid;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\AuditTypeInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Provides human-readable EntityType column value and EntityTypes filter list for audit data grid.
 */
class EntityTypeProvider
{
    public function __construct(
        private readonly EntityClassNameProviderInterface $entityClassNameProvider,
        private readonly AuditConfigProvider $configProvider,
        private readonly FeatureChecker $featureChecker,
        private readonly AuditTypeInterface $auditTypes
    ) {
    }

    public function getEntityType(): callable|\Closure
    {
        return function (ResultRecord $record) {
            $objectClass = (string)$record->getValue('objectClass');

            return $this->auditTypes->getTypeLabel($objectClass)
                ?? $this->entityClassNameProvider->getEntityClassName($objectClass);
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

        foreach ($this->auditTypes->getTypes() as $objectClass => $label) {
            $result[$label] = $objectClass;
        }

        // Order by the visible label (the array key), not by the entity class.
        ksort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }
}
