<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\ORM\Walker\PostgreSqlOrderByNullsOutputResultModifier as OutputResultModifier;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides totals aggregation, which will be shown in grid's footer.
 *
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

    /** @var DateTimeFormatterInterface */
    protected $dateTimeFormatter;

    /** @var AclHelper */
    protected $aclHelper;

    protected ?DoctrineHelper $doctrineHelper = null;

    /** @var array */
    protected $groupParts = [];

    public function __construct(
        TranslatorInterface $translator,
        NumberFormatter $numberFormatter,
        DateTimeFormatterInterface $dateTimeFormatter,
        AclHelper $aclHelper
    ) {
        $this->translator        = $translator;
        $this->numberFormatter   = $numberFormatter;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->aclHelper         = $aclHelper;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper): void
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return parent::isApplicable($config) && $config->isOrmDatasource();
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
                if ($rowConfig[Configuration::TOTALS_DISABLED]
                    || ($onlyOnePage && $rowConfig[Configuration::TOTALS_HIDE_IF_ONE_PAGE_KEY])
                ) {
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
        return -PHP_INT_MAX;
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
                if (isset($total[Configuration::TOTALS_DIVISOR_KEY])) {
                    $divisor = (int) $total[Configuration::TOTALS_DIVISOR_KEY];
                    if ($divisor != 0) {
                        $totalValue = $totalValue / $divisor;
                    }
                }
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
                            if (preg_match('/^(.*)\sas\s(.*)$/is', $part, $matches)) {
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
        // this method requires refactoring, see BAP-17427 for details
        $totalQueries = [];
        foreach ($columnsConfig as $field => $totalData) {
            if (isset($totalData[Configuration::TOTALS_SQL_EXPRESSION_KEY])
                && $totalData[Configuration::TOTALS_SQL_EXPRESSION_KEY]
            ) {
                $totalQueries[] = $totalData[Configuration::TOTALS_SQL_EXPRESSION_KEY] . ' AS ' . $field;
            }
        };

        $qb = clone $this->masterQB;
        if (!$perPage) {
            $qb->setFirstResult(null)->setMaxResults(null);
        }
        $this->addPageLimits($qb, $pageData, $perPage);
        $qb->select($totalQueries);
        $qb->resetDQLParts(['groupBy', 'having', 'orderBy']);

        QueryBuilderUtil::removeUnusedParameters($qb);

        $query = $qb->getQuery();

        if (!$skipAclWalkerCheck) {
            $query = $this->aclHelper->apply($query);
        }

        $resultData = $query
            ->setFirstResult(null)
            ->setMaxResults(1)
            ->getScalarResult();

        return array_shift($resultData);
    }

    protected function addPageLimits(QueryBuilder $dataQueryBuilder, ResultsObject $pageData, $perPage)
    {
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($dataQueryBuilder);
        $rootEntity = QueryBuilderUtil::getSingleRootEntity($dataQueryBuilder);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifierFieldName($rootEntity);
        $field = QueryBuilderUtil::getField($rootAlias, $identifier);
        $alias = '_identifier';

        $clonedQb = clone $dataQueryBuilder;
        $clonedQb->addSelect(sprintf('GROUP_CONCAT(%s) as %s', $field, $alias));

        $clonedQuery = $clonedQb->getQuery();
        $clonedQuery->setHint(OutputResultModifier::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS, true);
        $result = $clonedQuery->getArrayResult();

        $ids = array_reduce($result, fn ($accum, $data) => array_merge($accum, explode(',', $data[$alias])), []);

        $dataQueryBuilder->andWhere($dataQueryBuilder->expr()->in($field, ':identifiers'));
        $dataQueryBuilder->setParameter(':identifiers', array_unique($ids));
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
