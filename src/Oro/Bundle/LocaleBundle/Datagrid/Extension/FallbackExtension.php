<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\Extension;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\FallbackProperty;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

use Oro\Component\PropertyAccess\PropertyAccessor;

class FallbackExtension extends AbstractExtension
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, LocalizationHelper $localizationHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;

        $this->propertyAccessor = new PropertyAccessor();
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
        $properties = $this->getProperties($config);

        return count($properties) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        if (null === $this->localizationHelper->getCurrentLocalization()) {
            return;
        }

        $properties = $this->getProperties($config);

        foreach (array_keys($properties) as $name) {
            $config->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $name));
            $config->offsetUnsetByPath(sprintf('[filters][columns][%s]', $name));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (null !== $this->localizationHelper->getCurrentLocalization()) {
            return;
        }

        list(, $rootEntityAlias) = $this->getRootEntityNameAndAlias($config);
        if (!$rootEntityAlias) {
            return;
        }

        $properties = $this->getProperties($config);

        /* @var $queryBuilder QueryBuilder */
        $queryBuilder = $datasource->getQueryBuilder();

        foreach ($properties as $name => $definition) {
            $propertyPath = $definition[FallbackProperty::DATA_NAME_KEY];
            if (false === strpos($propertyPath, '.')) {
                $propertyPath = sprintf('%s.%s', $rootEntityAlias, $propertyPath);
            }

            $joinAlias = Inflector::pluralize($name);
            $join = Inflector::pluralize($propertyPath);

            $queryBuilder
                ->addSelect(sprintf('%s.string as %s', $joinAlias, $name))
                ->innerJoin($join, $joinAlias, Expr\Join::WITH, $joinAlias . '.localization IS NULL');
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

        list($rootEntity, $rootEntityAlias) = $this->getRootEntityNameAndAlias($config);
        if (!$rootEntity || !$rootEntityAlias) {
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
                $data[$name] = $this->resolveValue($entity, $definition[FallbackProperty::DATA_NAME_KEY]);
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
     * @param string $fieldName
     * @param string $prefix
     * @return string
     */
    protected function getMethodName($fieldName, $prefix)
    {
        return $prefix . ucfirst(Inflector::camelize(Inflector::singularize($fieldName)));
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
                return isset($property[FallbackProperty::TYPE_KEY]) &&
                    $property[FallbackProperty::TYPE_KEY] == FallbackProperty::NAME;
            }
        );

        foreach ($properties as $name => &$property) {
            $property[FallbackProperty::NAME_KEY] = $name;

            if (!isset($property[FallbackProperty::DATA_NAME_KEY])) {
                $property[FallbackProperty::DATA_NAME_KEY] = $name;
            }
        }

        return $properties;
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getRootEntityNameAndAlias(DatagridConfiguration $config)
    {
        $rootEntity = null;
        $rootEntityAlias = null;

        $from = $config->offsetGetByPath('[source][query][from]');
        if ($from) {
            $firstFrom = current($from);
            if (!empty($firstFrom['table']) && !empty($firstFrom['alias'])) {
                $rootEntity = $this->updateEntityClass($firstFrom['table']);
                $rootEntityAlias = $firstFrom['alias'];
            }
        }

        return [$rootEntity, $rootEntityAlias];
    }

    /**
     * @param string $entity
     * @return string
     */
    protected function updateEntityClass($entity)
    {
        return $this->doctrineHelper->getEntityMetadata($entity)->getName();
    }
}
