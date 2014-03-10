<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameters;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use Oro\Bundle\TranslationBundle\Translation\Translator;

class OrmTotalsExtension extends AbstractExtension
{
    /** @var RequestParameters */
    protected $requestParams;

    /** @var  Translator */
    protected $translator;

    /** @var QueryBuilder */
    protected $masterQB;

    /** @var NumberFormatter */
    protected $numberFormatter;

    /** @var DateTimeFormatter */
    protected $dateTimeFormatter;

    /**
     * @var array
     */
    protected $groupParts = [];

    public function __construct(
        Translator $translator,
        NumberFormatter $numberFormatter,
        DateTimeFormatter $dateTimeFormatter,
        RequestParameters $requestParams = null
    ) {
        $this->requestParams     = $requestParams;
        $this->translator        = $translator;
        $this->numberFormatter   = $numberFormatter;
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(Builder::DATASOURCE_TYPE_PATH) == OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $this->masterQB = clone $datasource->getQueryBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $renderPerPageRows = $result['options']['totalRecords'] !== count($result['data']);
        $totals            = $config->offsetGetByPath(Configuration::TOTALS_PATH);
        if (null != $totals && !empty($result['data'])) {
            foreach ($totals as $rowName => &$rowConfig) {
                if ($rowConfig[Configuration::TOTALS_PER_PAGE_ROW]
                    && $rowConfig[Configuration::TOTALS_HIDE_ON_FULL_SET]
                    && !$renderPerPageRows
                ) {
                    unset($totals[$rowName]);
                    continue;
                }

                $totalQueries = [];
                foreach ($rowConfig['columns'] as $field => $totalData) {
                    if (isset($totalData['query']) && $totalData['query']) {
                        $totalQueries[] = $totalData['query'] . ' AS ' . $field;
                    }
                };

                $this->appendData(
                    $this->getData(
                        $result,
                        $totalQueries,
                        $this->getGroupParts(),
                        $rowConfig[Configuration::TOTALS_PER_PAGE_ROW]
                    ),
                    $rowConfig
                );
            }
        } else {
            $totals = [];
        }

        $totals = $this->clearTotalsData($totals);
        $result->offsetAddToArray('options', ['totals' => $totals]);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $metaData)
    {
        $totals = $config->offsetGetByPath(Configuration::TOTALS_PATH);
        $metaData
            ->offsetAddToArray('state', ['totals' => $totals])
            ->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['orodatagrid/js/totals-builder']);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        // should visit after all extensions
        return -250;
    }

    /**
     * @return array
     */
    protected function getGroupParts()
    {
        if (empty($this->groupParts)) {
            $groupParts   = [];
            $groupByParts = $this->masterQB->getDQLPart('groupBy');
            if (!empty($groupByParts)) {
                /** @var GroupBy $groupByPart */
                foreach ($groupByParts as $groupByPart) {
                    $groupParts = array_merge($groupParts, $groupByPart->getParts());
                }
            }
            $this->groupParts = $groupParts;
        }

        return $this->groupParts;

    }


    /**
     * @param array $data
     * @param array $rowConfig
     */
    protected function appendData($data, &$rowConfig)
    {
        if (!empty($data)) {
            foreach ($rowConfig['columns'] as $field => &$total) {
                if (isset($data[0][$field])) {
                    $totalValue = $data[0][$field];
                    if (isset($total[Configuration::TOTALS_FORMATTER])) {
                        $totalValue = $this->applyFrontendFormatting(
                            $totalValue,
                            $total[Configuration::TOTALS_FORMATTER]
                        );
                    }
                    $total['total'] = $totalValue;
                }
                if (isset($total['label'])) {
                    $total['label'] = $this->translator->trans($total['label']);
                }
            };
        }
    }

    /**
     * @param array $totals
     * @return array
     */
    protected function clearTotalsData($totals)
    {
        foreach ($totals as $rowName => $rowData) {
            if (isset($rowData['per_page'])) {
                unset($totals[$rowName]['per_page']);
            }
            foreach ($rowData['columns'] as $field => $totalData) {
                if (isset($totalData['query'])) {
                    unset($totals[$rowName]['columns'][$field]['query']);
                }
            }
        }

        return $totals;
    }

    /**
     * @param $result
     * @param $totalQueries
     * @param $groupParts
     * @param bool $perPage
     * @return array
     */
    protected function getData($result, $totalQueries, $groupParts, $perPage = false)
    {
        $rootIdentifier = [];
        $query          = clone $this->masterQB;
        if (empty($groupParts)) {
            $rootIdentifiers = $query->getEntityManager()
                ->getClassMetadata($query->getRootEntities()[0])->getIdentifier();
            $rootAlias       = $query->getRootAliases()[0];
            foreach ($rootIdentifiers as $field) {
                $rootIdentifier[] = [
                    'fieldAlias'  => $field,
                    'alias'       => $field,
                    'entityAlias' => $rootAlias
                ];
            }
        } else {
            foreach ($groupParts as $groupPart) {
                if (strpos($groupPart, '.')) {
                    list($rootAlias, $rootIdentifierPart) = explode('.', $groupPart);
                    $rootIdentifier[] = [
                        'fieldAlias'  => $rootIdentifierPart,
                        'entityAlias' => $rootAlias,
                        'alias'       => $rootIdentifierPart
                    ];
                } else {
                    $selectParts = $this->masterQB->getDQLPart('select');
                    /** @var Select $selectPart */
                    foreach ($selectParts as $selectPart) {
                        foreach ($selectPart->getParts() as $part) {
                            if (preg_match('/^(.*)\sas\s(.*)$/i', $part, $matches)) {
                                if (count($matches) == 3 && $groupPart == $matches[2]) {
                                    $rootIdentifier[] = [
                                        'fieldAlias' => $matches[1],
                                        'alias'      => $matches[2]
                                    ];
                                }
                            } else {
                                $rootIdentifier[] = [
                                    'fieldAlias' => $groupPart,
                                    'alias'      => $groupPart
                                ];
                            }
                        }
                    }
                }
            }
        }

        $dataQueryBuilder = $query
            ->select($totalQueries)
            ->resetDQLPart('groupBy');

        if ($perPage) {
            $this->addPageLimits($dataQueryBuilder, $rootIdentifier, $result, $query);
        }

        return $dataQueryBuilder
            ->getQuery()
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->getScalarResult();
    }

    /**
     * @param $dataQueryBuilder
     * @param $rootIdentifier
     * @param $result
     * @param $query
     */
    protected function addPageLimits($dataQueryBuilder, $rootIdentifier, $result, $query)
    {
        foreach ($rootIdentifier as $identifier) {
            $ids = [];
            foreach ($result['data'] as $res) {
                $ids[] = $res[$identifier['alias']];
            }

            $field = isset($identifier['entityAlias'])
                ? $identifier['entityAlias'] . '.' . $identifier['fieldAlias']
                : $identifier['fieldAlias'];

            $dataQueryBuilder->andWhere($query->expr()->in($field, $ids));
        }
    }

    /**
     * @param mixed|null $val
     * @param string|null $formatter
     * @return string|null
     */
    protected function applyFrontendFormatting($val = null, $formatter = null)
    {
        if (null != $formatter) {
            switch ($formatter) {
                case PropertyInterface::TYPE_DATE:
                    $val = $this->dateTimeFormatter->formatDate($val);
                    break;
                case PropertyInterface::TYPE_DATETIME:
                    $val = $this->dateTimeFormatter->format($val);
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
        }

        return $val;
    }

    /**
     * @param array $totalRows
     * @param string $rowName
     * @param array $rowConfig
     * @param string $gridNme
     * @return array
     * @throws \Exception
     */
    protected function mergeTotals(&$totalRows, $rowName, $rowConfig, $gridNme)
    {
        if (isset($rowConfig[Configuration::TOTALS_EXTEND]) && $rowConfig[Configuration::TOTALS_EXTEND]) {
            if (!isset($totalRows[$rowConfig[Configuration::TOTALS_EXTEND]])) {
                throw new \Exception(sprintf(
                    'Total row %s definition in %s datagrid config does not exists',
                    $rowConfig[Configuration::TOTALS_EXTEND],
                    $gridNme
                ));
            }

            $parentConfig = $this->mergeTotals(
                $totalRows,
                $rowConfig[Configuration::TOTALS_EXTEND],
                $totalRows[$rowConfig[Configuration::TOTALS_EXTEND]],
                $gridNme
            );

            $rowConfig  = array_replace_recursive(
                $parentConfig,
                $totalRows[$rowName]
            );
            unset($totalRows[$rowName][Configuration::TOTALS_EXTEND]);

            $totalRows[$rowName] = $rowConfig;

        }

        return $rowConfig;
    }
}
