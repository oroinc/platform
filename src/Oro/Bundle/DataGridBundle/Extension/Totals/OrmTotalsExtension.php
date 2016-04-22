<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrmTotalsExtension extends AbstractExtension
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var QueryBuilder */
    protected $masterQB;

    /** @var NumberFormatter */
    protected $numberFormatter;

    /** @var DateTimeFormatter */
    protected $dateTimeFormatter;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var array */
    protected $groupParts = [];

    /**
     * @param TranslatorInterface $translator
     * @param NumberFormatter     $numberFormatter
     * @param DateTimeFormatter   $dateTimeFormatter
     * @param AclHelper           $aclHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        NumberFormatter $numberFormatter,
        DateTimeFormatter $dateTimeFormatter,
        AclHelper $aclHelper
    ) {
        $this->translator        = $translator;
        $this->numberFormatter   = $numberFormatter;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->aclHelper         = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() === OrmDatasource::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $totalRows = $this->validateConfiguration(
            new Configuration(),
            ['totals' => $config->offsetGetByPath(Configuration::TOTALS_PATH)]
        );

        if (!empty($totalRows)) {
            foreach ($totalRows as $rowName => $rowConfig) {
                $this->mergeTotals($totalRows, $rowName, $rowConfig, $config->getName());
            }

            $config->offsetSetByPath(Configuration::TOTALS_PATH, $totalRows);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $this->masterQB = clone $datasource->getQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $onlyOnePage  = $result->getTotalRecords() === count($result->getData());

        $totalData = [];
        $totals    = $config->offsetGetByPath(Configuration::TOTALS_PATH);
        if (null !== $totals && $result->getData()) {
            foreach ($totals as $rowName => $rowConfig) {
                if ($onlyOnePage && $rowConfig[Configuration::TOTALS_HIDE_IF_ONE_PAGE_KEY]) {
                    unset($totals[$rowName]);
                    continue;
                }

                $totalData[$rowName] = $this->getTotalData(
                    $rowConfig,
                    $this->getData(
                        $result,
                        $rowConfig['columns'],
                        $rowConfig[Configuration::TOTALS_PER_PAGE_ROW_KEY],
                        $config->isDatasourceSkipAclApply()
                    )
                );
            }
        }
        $result->offsetAddToArray('options', ['totals' => $totalData]);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $metaData)
    {
        $totals = $config->offsetGetByPath(Configuration::TOTALS_PATH);
        $metaData
            ->offsetAddToArray('initialState', ['totals' => $totals])
            ->offsetAddToArray('state', ['totals' => $totals])
            ->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['orodatagrid/js/totals-builder']);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should visit after all extensions
        return -250;
    }

    /**
     * Get Group by part of master query as array
     *
     * @return array
     */
    protected function getGroupParts()
    {
        if (empty($this->groupParts)) {
            $groupParts   = [];
            $groupByParts = $this->masterQB->getDQLPart('groupBy');
            if (!empty($groupByParts)) {
                /** @var Expr\GroupBy $groupByPart */
                foreach ($groupByParts as $groupByPart) {
                    foreach ($groupByPart->getParts() as $part) {
                        $groupParts = array_merge(
                            $groupParts,
                            array_map('trim', explode(',', $part))
                        );
                    }
                }
            }
            $this->groupParts = $groupParts;
        }

        return $this->groupParts;

    }

    /**
     * Get total row frontend data
     *
     * @param array $rowConfig Total row config
     * @param array $data Db result data for current total row config
     * @return array Array with array of columns total values and labels
     */
    protected function getTotalData($rowConfig, $data)
    {
        if (empty($data)) {
            return [];
        }

        $columns = [];
        foreach ($rowConfig['columns'] as $field => $total) {
            $column = [];
            if (isset($data[$field])) {
                $totalValue = $data[$field];
                if (isset($total[Configuration::TOTALS_FORMATTER_KEY])) {
                    $totalValue = $this->applyFrontendFormatting(
                        $totalValue,
                        $total[Configuration::TOTALS_FORMATTER_KEY]
                    );
                }
                $column['total'] = $totalValue;
            }
            if (isset($total[Configuration::TOTALS_LABEL_KEY])) {
                $column[Configuration::TOTALS_LABEL_KEY] =
                    $this->translator->trans($total[Configuration::TOTALS_LABEL_KEY]);
            }
            $columns[$field] = $column;
        };

        return ['columns' => $columns];
    }

    /**
     * Get root entities config data
     *
     * @param QueryBuilder $query
     * @return array with root entities config
     */
    protected function getRootIds(QueryBuilder $query)
    {
        $groupParts = $this->getGroupParts();
        $rootIds    = [];
        if (empty($groupParts)) {
            $rootIdentifiers = $query->getEntityManager()
                ->getClassMetadata($query->getRootEntities()[0])->getIdentifier();
            $rootAlias       = $query->getRootAliases()[0];
            foreach ($rootIdentifiers as $field) {
                $rootIds[] = [
                    'fieldAlias'  => $field,
                    'alias'       => $field,
                    'entityAlias' => $rootAlias
                ];
            }
        } else {
            foreach ($groupParts as $groupPart) {
                if (strpos($groupPart, '.')) {
                    list($rootAlias, $rootIdentifierPart) = explode('.', $groupPart);
                    $rootIds[] = [
                        'fieldAlias'  => $rootIdentifierPart,
                        'entityAlias' => $rootAlias,
                        'alias'       => $rootIdentifierPart
                    ];
                } else {
                    $selectParts = $this->masterQB->getDQLPart('select');
                    /** @var Expr\Select $selectPart */
                    foreach ($selectParts as $selectPart) {
                        foreach ($selectPart->getParts() as $part) {
                            if (preg_match('/^(.*)\sas\s(.*)$/i', $part, $matches)) {
                                if (count($matches) === 3 && $groupPart === $matches[2]) {
                                    $rootIds[] = [
                                        'fieldAlias' => $matches[1],
                                        'alias'      => $matches[2]
                                    ];
                                }
                            } else {
                                $rootIds[] = [
                                    'fieldAlias' => $groupPart,
                                    'alias'      => $groupPart
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $rootIds;
    }

    /**
     * Get total row data from database
     *
     * @param ResultsObject $pageData Grid page data
     * @param array $columnsConfig Total row columns config
     * @param bool $perPage Get data only for page data or for all data
     * @param bool $skipAclWalkerCheck Check Acl with acl helper or not
     * @return array
     */
    protected function getData(ResultsObject $pageData, $columnsConfig, $perPage = false, $skipAclWalkerCheck = false)
    {
        // todo: Need refactor this method. If query has not order by part and doesn't have id's in select, result
        //       can be unexpected
        $totalQueries = [];
        foreach ($columnsConfig as $field => $totalData) {
            if (isset($totalData[Configuration::TOTALS_SQL_EXPRESSION_KEY])
                && $totalData[Configuration::TOTALS_SQL_EXPRESSION_KEY]
            ) {
                $totalQueries[] = $totalData[Configuration::TOTALS_SQL_EXPRESSION_KEY] . ' AS ' . $field;
            }
        };

        $queryBuilder = clone $this->masterQB;
        $queryBuilder
            ->select($totalQueries)
            ->resetDQLPart('groupBy');

        $parameters = $queryBuilder->getParameters();
        if ($parameters->count()) {
            $queryBuilder->resetDQLPart('where')
                ->resetDQLPart('having')
                ->setParameters(new ArrayCollection());
        }

        $this->addPageLimits($queryBuilder, $pageData, $perPage);

        $query = $queryBuilder->getQuery();

        if (!$skipAclWalkerCheck) {
            $query = $this->aclHelper->apply($query);
        }

        $resultData = $query
            ->setFirstResult(null)
            ->setMaxResults(1)
            ->getScalarResult();

        return array_shift($resultData);
    }

    /**
     * Add "in" expression as page limit to query builder
     *
     * @param QueryBuilder $dataQueryBuilder
     * @param ResultsObject $pageData
     * @param bool $perPage
     */
    protected function addPageLimits(QueryBuilder $dataQueryBuilder, ResultsObject $pageData, $perPage)
    {
        $rootIdentifiers = $this->getRootIds($dataQueryBuilder);

        if (!$perPage) {
            $queryBuilder = clone $this->masterQB;
            $data = $queryBuilder
                ->getQuery()
                ->setFirstResult(null)
                ->setMaxResults(null)
                ->getScalarResult();
        } else {
            $data = $pageData->getData();
        }
        foreach ($rootIdentifiers as $identifier) {
            $ids = ArrayUtil::arrayColumn($data, $identifier['alias']);

            $field = isset($identifier['entityAlias'])
                ? $identifier['entityAlias'] . '.' . $identifier['fieldAlias']
                : $identifier['fieldAlias'];

            $filteredIds = array_filter($ids);
            if (empty($filteredIds)) {
                continue;
            }

            $dataQueryBuilder->andWhere($dataQueryBuilder->expr()->in($field, $ids));
        }

    }

    /**
     * Apply formatting to totals values
     *
     * @param mixed|null $val
     * @param string|null $formatter
     * @return string|null
     */
    protected function applyFrontendFormatting($val = null, $formatter = null)
    {
        if (null === $formatter) {
            return $val;
        }

        switch ($formatter) {
            case PropertyInterface::TYPE_DATE:
                $val = $this->dateTimeFormatter->formatDate($val);
                break;
            case PropertyInterface::TYPE_DATETIME:
                $val = $this->dateTimeFormatter->format($val);
                break;
            case PropertyInterface::TYPE_TIME:
                $val = $this->dateTimeFormatter->formatTime($val);
                break;
            case PropertyInterface::TYPE_DECIMAL:
                $val = $this->numberFormatter->formatDecimal($val);
                break;
            case PropertyInterface::TYPE_INTEGER:
                $val = $this->numberFormatter->formatDecimal($val);
                break;
            case PropertyInterface::TYPE_PERCENT:
                $val = $this->numberFormatter->formatPercent($val);
                break;
            case PropertyInterface::TYPE_CURRENCY:
                $val = $this->numberFormatter->formatCurrency($val);
                break;
        }

        return $val;
    }

    /**
     * Merge total rows configs
     *
     * @param array $totalRows
     * @param string $rowName
     * @param array $rowConfig
     * @param string $gridName
     * @return array
     * @throws LogicException
     */
    protected function mergeTotals(&$totalRows, $rowName, $rowConfig, $gridName)
    {
        if (isset($rowConfig[Configuration::TOTALS_EXTEND_KEY]) && $rowConfig[Configuration::TOTALS_EXTEND_KEY]) {
            if (!isset($totalRows[$rowConfig[Configuration::TOTALS_EXTEND_KEY]])) {
                throw new LogicException(sprintf(
                    'Total row "%s" definition in "%s" datagrid config does not exist',
                    $rowConfig[Configuration::TOTALS_EXTEND_KEY],
                    $gridName
                ));
            }

            $parentConfig = $this->mergeTotals(
                $totalRows,
                $rowConfig[Configuration::TOTALS_EXTEND_KEY],
                $totalRows[$rowConfig[Configuration::TOTALS_EXTEND_KEY]],
                $gridName
            );

            $rowConfig = array_replace_recursive(
                $parentConfig,
                $totalRows[$rowName]
            );
            unset($totalRows[$rowName][Configuration::TOTALS_EXTEND_KEY]);

            $totalRows[$rowName] = $rowConfig;

        }

        return $rowConfig;
    }
}
