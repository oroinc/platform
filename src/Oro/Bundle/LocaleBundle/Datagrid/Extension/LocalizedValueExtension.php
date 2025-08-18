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
     */
    #[\Override]
    public function getPriority()
    {
        return 200;
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && count($this->getProperties($config)) > 0;
    }

    #[\Override]
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
     */
    #[\Override]
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (null !== $this->localizationHelper->getCurrentLocalization()) {
            return;
        }

        $rootEntityAlias = $config->getOrmQuery()->getRootAlias();
        if (!$rootEntityAlias) {
            return;
        }

        $queryBuilder = $datasource->getQueryBuilder();
        $exprBuilder = $queryBuilder->expr();
        $rootAlias = $config->getOrmQuery()->getRootAlias();

        if (!$rootAlias) {
            return;
        }

        $properties = $this->getProperties($config);

        foreach ($properties as $name => $definition) {
            $this->processProperty($queryBuilder, $exprBuilder, $rootAlias, $name, $definition);
        }
    }

    private function processProperty(QueryBuilder $qb, $expr, string $rootAlias, string $name, array $definition): void
    {
        QueryBuilderUtil::checkIdentifier($name);

        $propertyPath = $this->normalizePropertyPath($definition, $rootAlias);
        $allowEmpty = \array_key_exists(LocalizedValueProperty::ALLOW_EMPTY, $definition);
        $joinType = $allowEmpty ? Join::LEFT_JOIN : Join::INNER_JOIN;

        $this->joinDefaultLocalizedValue(
            $qb,
            $this->inflector->pluralize($propertyPath),
            $this->inflector->pluralize($name),
            $name,
            $joinType
        );

        if ($allowEmpty) {
            # In case of left join , use only default localization.
            $this->applyEmptyOrDefaultCondition($qb, $expr, $propertyPath, $name);
        }

        if ($qb->getDQLPart('groupBy')) {
            $qb->addGroupBy($name);
        }
    }

    private function normalizePropertyPath(array $definition, string $rootAlias): string
    {
        $path = $definition[LocalizedValueProperty::DATA_NAME_KEY];

        return str_contains($path, '.') ? $path : "$rootAlias.$path";
    }

    private function applyEmptyOrDefaultCondition(QueryBuilder $qb, $expr, string $propertyPath, string $name): void
    {
        $localizedAliasParts = explode('.', $propertyPath);
        $pluralAlias = $this->inflector->pluralize($name);
        $fallbackAlias = reset($localizedAliasParts);
        $whereExpr = $expr->orX(
            $expr->andX(
                QueryBuilderUtil::sprintf('%s IS EMPTY', $propertyPath),
                $expr->isNull(QueryBuilderUtil::getField($pluralAlias, 'id'))
            ),
            $expr->andX(
                QueryBuilderUtil::sprintf('%s IS NOT EMPTY', $propertyPath),
                $expr->isNotNull(QueryBuilderUtil::getField($pluralAlias, 'id'))
            ),
            $expr->isNull(QueryBuilderUtil::getField($fallbackAlias, 'id'))
        );

        $qb->andWhere($whereExpr);
    }

    #[\Override]
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
