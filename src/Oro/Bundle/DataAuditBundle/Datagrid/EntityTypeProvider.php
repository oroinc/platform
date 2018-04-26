<?php

namespace Oro\Bundle\DataAuditBundle\Datagrid;

use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class EntityTypeProvider
{
    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var AuditConfigProvider */
    protected $configProvider;

    /**
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     * @param AuditConfigProvider              $configProvider
     */
    public function __construct(
        EntityClassNameProviderInterface $entityClassNameProvider,
        AuditConfigProvider $configProvider
    ) {
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * @param string $gridName
     * @param string $keyName
     * @param array  $node
     *
     * @return callable
     */
    public function getEntityType($gridName, $keyName, $node)
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
    public function getEntityTypes()
    {
        $result = [];
        $classNames = $this->configProvider->getAllAuditableEntities();
        foreach ($classNames as $className) {
            $label = $this->entityClassNameProvider->getEntityClassName($className);
            if ($label) {
                $result[$label] = $className;
            }
        }
        asort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }
}
