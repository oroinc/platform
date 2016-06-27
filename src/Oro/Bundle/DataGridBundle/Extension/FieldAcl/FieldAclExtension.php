<?php

namespace Oro\Bundle\DataGridBundle\Extension\FieldAcl;

use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\From;

use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\EntityObjectReference;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class FieldAclExtension extends AbstractExtension
{
    const FIELD_ACL = 'field_acl';

    const OWNER_FIELD_PLACEHOLDER = '%s_owner_id';
    
    const ORGANIZARION_FIELD_PLACEHOLDER = '%s_organization_id';

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var AuthorizationCheckerInterface */
    protected $authChecker;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var array */
    protected $fieldAclConfig = [];

    /** @var array */
    protected $queryAliases = [];

    /**
     * @param OwnershipMetadataProvider     $ownershipMetadataProvider
     * @param EntityClassResolver           $entityClassResolver
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ConfigProvider                $configProvider
     */
    public function __construct(
        OwnershipMetadataProvider $ownershipMetadataProvider,
        EntityClassResolver $entityClassResolver,
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigProvider $configProvider
    ) {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->entityClassResolver = $entityClassResolver;
        $this->authChecker = $authorizationChecker;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() === OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetOr(ColumnsExtension::COLUMNS_PATH, []);
        $fieldAclConfig = $config->offsetGetByPath(Configuration::FIELDS_ACL);

        // move config from columns to fields_acl section
        foreach ($columns as $columnName => $columnConfig) {
            $fieldAclConfig['columns'][$columnName] = array_key_exists(self::FIELD_ACL, $columnConfig)
                ? $columnConfig[self::FIELD_ACL]
                : null;
        }

        $validated = $this->validateConfiguration(
            new Configuration(),
            ['fields_acl' => $fieldAclConfig]
        );

        $config->offsetSetByPath(
            Configuration::FIELDS_ACL,
            $validated
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return 255;
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if (count($this->fieldAclConfig) === 0 || count($this->queryAliases) === 0) {
            return;
        }

        /** @var ResultRecord $record */
        foreach ($result->getData() as $record) {
            $domainObjects = [];
            foreach ($this->fieldAclConfig as $column => $fieldInfo) {
                $alias = $fieldInfo[0];
                if (!array_key_exists($alias, $domainObjects)) {
                    $domainObjects[$alias] = new EntityObjectReference(
                        $this->queryAliases[$alias],
                        $record->getValue('id'),
                        $record->getValue(sprintf(self::OWNER_FIELD_PLACEHOLDER, $alias)),
                        $record->getValue(sprintf(self::ORGANIZARION_FIELD_PLACEHOLDER, $alias))
                    );
                }
                $entityObject = $domainObjects[$alias];
                if (!$this->authChecker->isGranted('VIEW', new FieldVote($entityObject, $fieldInfo[1]))) {
                    // set column value to null if user have no access to view this value
                    $record->setValue($column, null);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $fieldAclConfig = $config->offsetGetByPath(Configuration::COLUMNS_PATH);
        if (!is_array($fieldAclConfig) || count($fieldAclConfig) === 0) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $datasource->getQueryBuilder();
        $this->collectEntityAliases($qb);
        
        // given datagrid have no entities with enabled Field ACL.
        if (count($this->queryAliases) === 0) {
            return;
        }

        $select = $qb->getDQLPart('select');
        // collect field ACL config
        $aliases = [];
        foreach ($fieldAclConfig as $columnName => $fieldData) {
            if ($fieldData[PropertyInterface::DISABLED_KEY] === false) {
                continue;
            }

            if (array_key_exists(PropertyInterface::DATA_NAME_KEY, $fieldData)) {
                list($alias, $fieldName) = explode('.', $fieldData[PropertyInterface::DATA_NAME_KEY]);
            } else {
                $fieldName = $columnName;
                $alias = $this->tryToGetAliasFromSelectPart($select, $fieldName);
                // if we can't find alias for column - skip this column
                if ($alias === null) {
                    continue;
                }
            }

            // check if given entity should to do field ACL checks
            if (array_key_exists($alias, $this->queryAliases)) {
                $this->fieldAclConfig[$columnName] = [$alias, $fieldName];
                if (!in_array($alias, $aliases)) {
                    $aliases[] = $alias;
                }
            }
        }

        // add owner data to request
        $this->addIdentitySelectsToQuery($qb, $aliases);
    }

    /**
     * Collect query aliases with class names
     *
     * @param QueryBuilder $qb
     */
    protected function collectEntityAliases(QueryBuilder $qb)
    {
        $fromParts = $qb->getDQLPart('from');
        /** @var From $fromPart */
        foreach ($fromParts as $fromPart) {
            $className = $this->entityClassResolver->getEntityClass($fromPart->getFrom());
            // check if Field ACL enabled for given class
            if ($this->configProvider->hasConfig($className)) {
                $securityConfig = $this->configProvider->getConfig($className);
                if ($securityConfig->get('field_acl_supported') && $securityConfig->get('field_acl_enabled')) {
                    $this->queryAliases[$fromPart->getAlias()] = $className;
                }
            }
        }
    }

    /**
     * Add owner fields to query by alias
     *
     * @param QueryBuilder $qb
     * @param array        $aliases
     */
    protected function addIdentitySelectsToQuery(QueryBuilder $qb, array $aliases)
    {
        foreach ($aliases as $alias) {
            if (!array_key_exists($alias, $this->queryAliases)) {
                continue;
            }
            $entityClassName = $this->queryAliases[$alias];
            $metadata = $this->ownershipMetadataProvider->getMetadata($entityClassName);
            $ownerField = $metadata->getOwnerFieldName();
            if ($ownerField) {
                $selectExpr = sprintf(
                    'IDENTITY(%s.%s) as %s',
                    $alias,
                    $ownerField,
                    sprintf(self::OWNER_FIELD_PLACEHOLDER, $alias)
                );
                $qb->addSelect($selectExpr);
            }

            $organizationField = $metadata->getGlobalOwnerFieldName();
            if ($organizationField) {
                $selectExpr = sprintf(
                    'IDENTITY(%s.%s) as %s',
                    $alias,
                    $organizationField,
                    $organizationField .
                    sprintf(self::ORGANIZARION_FIELD_PLACEHOLDER, $alias)
                );
                $qb->addSelect($selectExpr);
            }
        }
    }

    /**
     * @param array  $select
     * @param string $columnName
     *
     * @return null
     */
    protected function tryToGetAliasFromSelectPart(array $select, $columnName)
    {
        foreach (array_keys($this->queryAliases) as $queryAlias) {
            $testField = sprintf('%s.%s', $queryAlias, $columnName);
            /** @var Select $selectExpr */
            foreach ($select as $selectExpr) {
                foreach ($selectExpr->getParts() as $part) {
                    if ($part === $testField) {
                        return $queryAlias;
                    }
                }
            }
        }

        return null;
    }
}
