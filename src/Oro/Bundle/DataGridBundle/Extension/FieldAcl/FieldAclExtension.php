<?php

namespace Oro\Bundle\DataGridBundle\Extension\FieldAcl;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Applies field ACL to datagrid and sets column value to null if user is not permitted to view this field value.
 */
class FieldAclExtension extends AbstractExtension
{
    /** @var array [column name => [entity alias, field name], ...] */
    private $fieldAclConfig = [];

    /**
     * @var array [entity alias => [
     *                      entity class,
     *                      entity id field alias,
     *                      organization id field alias,
     *                      owner id field alias
     *                  ],
     *                  ...
     *              ]
     */
    private $ownershipFields = [];

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ConfigManager $configManager,
        private OwnershipQueryHelper $ownershipQueryHelper,
    ) {
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return parent::isApplicable($config) && $config->isOrmDatasource();
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config)
    {
        $validated = $this->validateConfiguration(
            new Configuration(),
            ['fields_acl' => $config->offsetGetByPath(Configuration::FIELDS_ACL)]
        );

        $config->offsetSetByPath(Configuration::FIELDS_ACL, $validated);
    }

    #[\Override]
    public function getPriority()
    {
        return 255;
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if (empty($this->fieldAclConfig) || empty($this->ownershipFields)) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $result->getData();
        foreach ($records as $record) {
            foreach ($this->fieldAclConfig as $fieldData) {
                [$entityAlias, $fieldName, $columnName] = $fieldData;
                $entityReference = $this->getEntityReference($record, $entityAlias);
                if (
                    null !== $entityReference
                    && !$this->authorizationChecker->isGranted('VIEW', new FieldVote($entityReference, $fieldName))
                ) {
                    // set column value to null if user does not have an access to view this value
                    $record->setValue($columnName, null);
                }
            }
        }
    }

    #[\Override]
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        /** @var OrmDatasource $datasource */

        /** @var array $fieldAclConfig */
        $fieldAclConfig = $config->offsetGetByPath(Configuration::COLUMNS_PATH);
        if (empty($fieldAclConfig)) {
            return;
        }

        $qb = $datasource->getQueryBuilder();
        $this->ownershipFields = $this->ownershipQueryHelper->addOwnershipFields(
            $qb,
            fn ($entityClass, $entityAlias) => $this->isFieldAclEnabled($entityClass)
        );

        foreach ($fieldAclConfig as $columnName => $fieldConfig) {
            if (
                array_key_exists(PropertyInterface::DISABLED_KEY, $fieldConfig)
                && $fieldConfig[PropertyInterface::DISABLED_KEY]
            ) {
                continue;
            }

            if (array_key_exists(PropertyInterface::DATA_NAME_KEY, $fieldConfig)) {
                $fieldExpr = $fieldConfig[PropertyInterface::DATA_NAME_KEY];
            } else {
                $fieldExpr = QueryBuilderUtil::getSelectExprByAlias($qb, $columnName);
            }

            $parts = explode('.', $fieldExpr);
            if (count($parts) === 2 && isset($this->ownershipFields[$parts[0]])) {
                $dataAlias = [$fieldConfig[PropertyInterface::COLUMN_NAME] ?? $columnName];
                $this->fieldAclConfig[$columnName] = array_merge($parts, $dataAlias);
            }
        }
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    protected function isFieldAclEnabled($entityClass)
    {
        $result = false;
        if ($this->configManager->hasConfig($entityClass)) {
            $entityConfig = $this->configManager->getEntityConfig('security', $entityClass);
            $result =
                $entityConfig->get('field_acl_supported')
                && $entityConfig->get('field_acl_enabled')
                && !$entityConfig->get('show_restricted_fields');
        }

        return $result;
    }

    private function getEntityReference(ResultRecord $record, $entityAlias)
    {
        [
            $entityClass,
            $entityIdFieldAlias,
            $organizationIdFieldAlias,
            $ownerIdFieldAlias
        ] = $this->ownershipFields[$entityAlias];

        $ownerId = $record->getValue($ownerIdFieldAlias);
        if (null === $ownerId) {
            return null;
        }
        return new ObjectIdentity('entity', $entityClass);
    }
}
