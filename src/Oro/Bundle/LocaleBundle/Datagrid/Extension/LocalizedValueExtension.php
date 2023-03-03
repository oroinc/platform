<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\Extension;

use Doctrine\Common\Collections\Collection;
use Doctrine\Inflector\Inflector;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Show localized values in grid according to the current localization
 */
class LocalizedValueExtension extends AbstractExtension
{
    use LocalizationQueryTrait;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    protected PropertyAccessorInterface $propertyAccessor;

    private Inflector $inflector;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        LocalizationHelper $localizationHelper,
        Inflector $inflector
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClassResolver = $entityClassResolver;
        $this->localizationHelper = $localizationHelper;

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->inflector = $inflector;
    }

    /**
     * Should be applied before formatter extension
     *
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 200;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && count($this->getProperties($config)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        if (null === $this->localizationHelper->getCurrentLocalization()) {
            return;
        }

        $properties = array_keys($this->getProperties($config));

        foreach ($properties as $propertyName) {
            $config->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $propertyName));
            $config->offsetUnsetByPath(sprintf('[filters][columns][%s]', $propertyName));
        }
    }

    /**
     * @param OrmDatasource $datasource
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (null !== $this->localizationHelper->getCurrentLocalization()) {
            return;
        }

        $rootEntityAlias = $config->getOrmQuery()->getRootAlias();
        if (!$rootEntityAlias) {
            return;
        }

        $properties = $this->getProperties($config);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $datasource->getQueryBuilder();
        $exprBuilder = $queryBuilder->expr();
        foreach ($properties as $name => $definition) {
            QueryBuilderUtil::checkIdentifier($name);
            $propertyPath = $definition[LocalizedValueProperty::DATA_NAME_KEY];

            $shouldAllowEmpty = \array_key_exists(LocalizedValueProperty::ALLOW_EMPTY, $definition);
            $joinType = $shouldAllowEmpty ? Join::LEFT_JOIN : Join::INNER_JOIN;

            if (!str_contains($propertyPath, '.')) {
                $propertyPath = sprintf('%s.%s', $rootEntityAlias, $propertyPath);
            }

            $this->joinDefaultLocalizedValue(
                $queryBuilder,
                $this->inflector->pluralize($propertyPath),
                $this->inflector->pluralize($name),
                $name,
                $joinType
            );

            // in case of left join , use only default localization
            if ($shouldAllowEmpty) {
                $localizedEntityAliasValues = explode('.', $propertyPath);
                $queryBuilder->andWhere(
                    $exprBuilder->orX(
                        $exprBuilder->isNotNull(QueryBuilderUtil::getField($this->inflector->pluralize($name), 'id')),
                        $exprBuilder->isNull(QueryBuilderUtil::getField(reset($localizedEntityAliasValues), 'id'))
                    )
                );
            }

            if ($queryBuilder->getDQLPart('groupBy')) {
                $queryBuilder->addGroupBy($name);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if (null === ($localization = $this->localizationHelper->getCurrentLocalization())) {
            return;
        }

        $rootEntity = $config->getOrmQuery()->getRootEntity($this->entityClassResolver);
        if (!$rootEntity) {
            return;
        }
        $rootEntityAlias = $config->getOrmQuery()->getRootAlias();
        if (!$rootEntityAlias) {
            return;
        }

        if (null === ($pkName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($rootEntity, false))) {
            return;
        }

        $properties = $this->getProperties($config);

        foreach ($result->getData() as $record) {
            /* @var $record ResultRecordInterface */
            if (null === ($primaryKey = $record->getValue($pkName))) {
                continue;
            }

            $entity = $this->doctrineHelper->getEntity($rootEntity, $primaryKey);

            $data = [];
            foreach ($properties as $name => $definition) {
                $data[$name] = $this->resolveValue($entity, $definition[LocalizedValueProperty::DATA_NAME_KEY]);
            }
            $record->addData($data);
        }
    }

    /**
     * @param object $entity
     * @param string $propertyPath
     * @return mixed
     */
    protected function resolveValue($entity, $propertyPath)
    {
        $isReadable = $this->propertyAccessor->isReadable($entity, $propertyPath);

        $value = $isReadable ? $this->propertyAccessor->getValue($entity, $propertyPath) : null;

        return $value instanceof Collection ? $this->localizationHelper->getLocalizedValue($value) : $value;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getProperties(DatagridConfiguration $config)
    {
        $properties = array_filter(
            $config->offsetGetOr(Configuration::PROPERTIES_KEY, []),
            function ($property) {
                return isset($property[LocalizedValueProperty::TYPE_KEY]) &&
                    $property[LocalizedValueProperty::TYPE_KEY] === LocalizedValueProperty::NAME;
            }
        );

        foreach ($properties as $name => &$property) {
            $property[LocalizedValueProperty::NAME_KEY] = $name;

            if (!isset($property[LocalizedValueProperty::DATA_NAME_KEY])) {
                $property[LocalizedValueProperty::DATA_NAME_KEY] = $name;
            }
        }

        return $properties;
    }
}
