<?php

namespace Oro\Bundle\WorkflowBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

class RestrictionsExtension extends AbstractExtension
{
    const ENTITY_RESTRICTIONS = 'entity_restrictions';
    const PROPERTY_ID_PATH    = '[properties][id]';
    const PROPERTIES_PATH     = '[properties]';

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;
    /**
     * @var RestrictionManager
     */
    protected $restrictionsManager;

    /** @var string|null */
    protected $entityClassName;

    /**
     * @param GridConfigurationHelper $gridConfigurationHelper
     * @param RestrictionManager      $restrictionManager
     */
    public function __construct(
        GridConfigurationHelper $gridConfigurationHelper,
        RestrictionManager $restrictionManager
    ) {
        $this->gridConfigurationHelper = $gridConfigurationHelper;
        $this->restrictionsManager = $restrictionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $this->parameters->get(Manager::REQUIRE_ALL_EXTENSIONS, true) &&
               null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH) &&
               $this->hasEntityClassRestrictions($config);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return bool
     */
    protected function hasEntityClassRestrictions(DatagridConfiguration $config)
    {
        $className = $this->getEntity($config);

        return $className
            ? $this->restrictionsManager->hasEntityClassRestrictions($className)
            : false;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    protected function getEntity(DatagridConfiguration $config)
    {
        if ($this->entityClassName === null) {
            $this->entityClassName = $this->gridConfigurationHelper->getEntity($config);
        }

        return $this->entityClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows    = $result->getData();
        if (empty($rows)) {
            return;
        }
        $idField = 'id';

        $restrictions    = $this->getEntitiesRestrictions(
            $this->getEntity($config),
            $this->extractEntityIds($rows, $idField)
        );

        $this->addRestrictionsToData($rows, $restrictions, $idField, self::ENTITY_RESTRICTIONS);
    }

    /**
     * Extract entity ids from rows by identifier.
     *
     * @param array  $rows
     * @param string $idField
     *
     * @return array
     */
    protected function extractEntityIds(array $rows, $idField)
    {
        return array_reduce(
            $rows,
            function ($entityIds, ResultRecord $item) use ($idField) {
                $entityIds[] = $item->getValue($idField);

                return $entityIds;
            },
            []
        );
    }

    /**
     * Add restrictions data to result rows for every entity id founded.
     *
     * @param array  $rows
     * @param array  $restrictions
     * @param string $identifier
     * @param        $restrictionColumnId
     *
     * @return array
     *
     */
    protected function addRestrictionsToData(array  $rows, array $restrictions, $identifier, $restrictionColumnId)
    {
        return array_map(
            function (ResultRecord $item) use ($restrictions, $identifier, $restrictionColumnId) {
                $id   = $item->getValue($identifier);
                $data = isset($restrictions[$id]) ? $restrictions[$id] : [];
                $item->addData([$restrictionColumnId => $data]);

                return $item;
            },
            $rows
        );
    }

    /**
     * @param string $entityClass
     * @param array  $ids
     *
     * @return array
     */
    protected function getEntitiesRestrictions($entityClass, array $ids)
    {
        $restrictionsData = $this->restrictionsManager->getEntitiesRestrictions($entityClass, $ids);
        $entityRestrictions = [];
        foreach ($restrictionsData as $restrictionData) {
            $ids = $restrictionData['ids'];
            unset($restrictionData['ids']);
            foreach ($ids as $id) {
                $entityRestrictions[$id][] = $restrictionData;
            }
        }

        return $entityRestrictions;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $properties = $config->offsetGetByPath(self::PROPERTIES_PATH, []);
        $property  = [self::ENTITY_RESTRICTIONS => $this->getPropertyDefinition()];
        $config->offsetSetByPath(self::PROPERTIES_PATH, array_merge($properties, $property));
    }

    /**
     * @return array
     */
    protected function getPropertyDefinition()
    {
        return [
            'type'           => 'callback',
            'frontend_type'  => self::ENTITY_RESTRICTIONS,
            'callable'       => function (ResultRecordInterface $record) {
                return $record->getValue(self::ENTITY_RESTRICTIONS);
            },
        ];
    }
}
