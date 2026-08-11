<?php

namespace Oro\Bundle\DataAuditBundle\Datagrid;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides human-readable EntityType column value and EntityTypes filter list for audit data grid.
 */
class EntityTypeProvider
{
    public function __construct(
        private readonly EntityClassNameProviderInterface $entityClassNameProvider,
        private readonly AuditConfigProvider $configProvider,
        private readonly FeatureChecker $featureChecker,
        private readonly TranslatorInterface $translator,
        private readonly ConfigAuditLevelProvider $levelProvider
    ) {
    }

    public function getEntityType(): callable|\Closure
    {
        return function (ResultRecord $record) {
            $objectClass = $record->getValue('objectClass');

            return $this->levelProvider->isConfigType($objectClass)
                ? $this->getConfigurationLevelLabel($objectClass)
                : $this->entityClassNameProvider->getEntityClassName($objectClass);
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

        // Every configuration level of this application, so that changes of each of them can be filtered.
        foreach (array_keys($this->levelProvider->all()) as $configClass) {
            $result[$this->getConfigurationLevelLabel($configClass)] = $configClass;
        }

        // Order by the visible label (the array key), not by the entity class.
        ksort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }

    /**
     * A configuration level is named by its own translation, and by a readable name derived from the
     * level itself when the bundle that contributed the scope ships no translation for it.
     */
    private function getConfigurationLevelLabel(string $objectClass): string
    {
        $labelKey = $this->levelProvider->getLabelKey($objectClass);
        $label = $labelKey ? $this->translator->trans($labelKey) : null;

        return $label && $label !== $labelKey ? $label : $this->levelProvider->getGenericLabel($objectClass);
    }
}
