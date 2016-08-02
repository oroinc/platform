<?php

namespace Oro\Bundle\LocaleBundle\Datagrid\Extension;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;

use Oro\Component\PropertyAccess\PropertyAccessor;

class LocalizedValueExtension extends AbstractExtension
{
    use LocalizationQueryTrait;

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
        return count($this->getProperties($config)) > 0;
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

        /** @var OrmDatasource $datasource */
        $queryBuilder = $datasource->getQueryBuilder();

        foreach ($properties as $name => $definition) {
            $propertyPath = $definition[LocalizedValueProperty::DATA_NAME_KEY];
            if (false === strpos($propertyPath, '.')) {
                $propertyPath = sprintf('%s.%s', $rootEntityAlias, $propertyPath);
            }

            $this->joinDefaultLocalizedValue(
                $queryBuilder,
                Inflector::pluralize($propertyPath),
                Inflector::pluralize($name),
                $name
            );

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
                $rootEntity = $this->getEntityClassName($firstFrom['table']);
                $rootEntityAlias = $firstFrom['alias'];
            }
        }

        return [$rootEntity, $rootEntityAlias];
    }

    /**
     * @param string $entity
     * @return string
     */
    protected function getEntityClassName($entity)
    {
        return $this->doctrineHelper->getEntityMetadata($entity)->getName();
    }
}
