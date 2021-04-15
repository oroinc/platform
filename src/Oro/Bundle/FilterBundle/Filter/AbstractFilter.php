<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Component\DoctrineUtils\ORM\DqlUtil;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Abstract filter class contains common filters functionality
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractFilter implements FilterInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var FilterUtility */
    protected $util;

    /** @var string */
    protected $name;

    /** @var array */
    protected $params;

    /** @var Form */
    protected $form;

    /** @var array */
    protected $unresolvedOptions = [];

    /** @var array [array, ...] */
    protected $additionalOptions = [];

    /** @var array */
    protected $state;

    /** @var array */
    protected $joinOperators = [];

    /**
     * @var string
     */
    protected $dataFieldName;

    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     */
    public function __construct(FormFactoryInterface $factory, FilterUtility $util)
    {
        $this->formFactory = $factory;
        $this->util        = $util;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $this->name   = $name;
        $this->params = $params;

        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);
        $this->unresolvedOptions = array_filter($options, 'is_callable');
        if (!$this->isLazy()) {
            $this->resolveOptions();
        } else {
            $unresolvedKeys = array_keys($this->unresolvedOptions);
            foreach ($unresolvedKeys as $key) {
                unset($this->params[FilterUtility::FORM_OPTIONS_KEY][$key]);
            }
        }
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type = $data['type'];
        $notExpression = $this->getJoinOperator($type);
        $useExists = $this->shouldUseExists($ds, $data);

        if ($notExpression && $useExists) {
            $type = $notExpression;
        }
        $comparisonExpr = $this->buildExpr($ds, $type, $this->getDataFieldName(), $data);
        if (!$comparisonExpr) {
            return true;
        }

        if ($useExists) {
            $comparisonExpr = $this->getExistsComparisonExpression($ds, $comparisonExpr, $notExpression);
        }

        $this->applyFilterToClause($ds, $comparisonExpr);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getForm()
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(
                $this->getFormType(),
                [],
                array_merge($this->getOr(FilterUtility::FORM_OPTIONS_KEY, []), ['csrf_protection' => false])
            );
        }

        return $this->form;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView = $this->getForm()->createView();
        $typeView = $formView->children['type'];

        $defaultMetadata = [
            'name'                     => $this->getName(),
            // use filter name if label not set
            'label'                    => ucfirst($this->name),
            'choices'                  => $typeView->vars['choices'],
        ];

        $metadata = array_diff_key(
            $this->get() ?: [],
            array_flip($this->util->getExcludeParams())
        );
        $metadata = $this->mapParams($metadata);
        $metadata = array_merge($defaultMetadata, $metadata);
        $metadata['lazy'] = $this->isLazy();

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOptions()
    {
        $this->params[FilterUtility::FORM_OPTIONS_KEY] = array_merge(
            $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []),
            array_map(
                function ($cb) {
                    return call_user_func($cb);
                },
                $this->unresolvedOptions
            )
        );
        $this->unresolvedOptions = [];

        $options = $this->params[FilterUtility::FORM_OPTIONS_KEY];
        foreach ($this->additionalOptions as $path) {
            $options = ArrayUtil::unsetPath($options, $path);
        }
        $this->params[FilterUtility::FORM_OPTIONS_KEY] = $options;
        $this->additionalOptions = [];
    }

    /**
     * {@inheritdoc}
     */
    public function setFilterState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterState()
    {
        return $this->state;
    }

    /**
     * Returns form type associated to this filter
     *
     * @return mixed
     */
    abstract protected function getFormType();

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param $fieldExpr
     * @param string|array|null $filter
     * @return array
     */
    protected function getSubQueryExpressionWithParameters(
        OrmFilterDatasourceAdapter $ds,
        $fieldExpr,
        $filter
    ): array {
        $subQb = $this->createSubQueryBuilder($ds, $filter);
        $subQb
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select($fieldExpr)
            ->andWhere(sprintf('%1$s = %1$s', $fieldExpr));

        $this->processSubQueryExpressionGroupBy($ds, $subQb, $fieldExpr);
        [$dql, $replacements] = $this->createDQLWithReplacedAliases($ds, $subQb);
        [$fieldAlias, $field] = explode('.', $fieldExpr);
        $replacedFieldExpr = sprintf('%s.%s', $replacements[$fieldAlias], $field);
        $oldExpr = sprintf('%1$s = %1$s', $replacedFieldExpr);
        $newExpr = sprintf('%s = %s', $replacedFieldExpr, $fieldExpr);
        $dql = strtr($dql, [$oldExpr => $newExpr]);

        return [$dql, $subQb->getParameters()];
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param mixed|null $filter
     * @return QueryBuilder
     */
    protected function createSubQueryBuilder(OrmFilterDatasourceAdapter $ds, $filter = null): QueryBuilder
    {
        $qb = clone $ds->getQueryBuilder();
        $qb->resetDQLPart('where');
        if ($filter) {
            $qb->andWhere($filter);
        }

        return $qb;
    }

    /**
     * Apply filter expression to having or where clause depending on configuration
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param mixed                            $expression
     * @param string                           $conditionType
     */
    protected function applyFilterToClause(
        FilterDatasourceAdapterInterface $ds,
        $expression,
        $conditionType = FilterUtility::CONDITION_AND
    ) {
        $ds->addRestriction(
            $expression,
            $this->getOr(FilterUtility::CONDITION_KEY, $conditionType),
            $this->getOr(FilterUtility::BY_HAVING_KEY, false)
        );
    }

    /**
     * Get param or throws exception
     *
     * @param string $paramName
     *
     * @throws \LogicException
     * @return mixed
     */
    protected function get($paramName = null)
    {
        $value = $this->params;

        if ($paramName !== null) {
            if (!isset($this->params[$paramName])) {
                throw new \LogicException(sprintf('Trying to access not existing parameter: "%s"', $paramName));
            }

            $value = $this->params[$paramName];
        }

        return $value;
    }

    /**
     * @param string $paramName
     *
     * @return bool
     */
    protected function has($paramName)
    {
        return isset($this->params[$paramName]);
    }

    /**
     * Get param if exists or default value
     *
     * @param string $paramName
     * @param null   $default
     *
     * @return mixed
     */
    protected function getOr($paramName = null, $default = null)
    {
        if ($paramName !== null) {
            return isset($this->params[$paramName]) ? $this->params[$paramName] : $default;
        }

        return $this->params;
    }

    /**
     * Process mapping params
     *
     * @param array $params
     *
     * @return array
     */
    protected function mapParams($params)
    {
        $keys     = [];
        $paramMap = $this->util->getParamMap();
        foreach (array_keys($params) as $key) {
            if (isset($paramMap[$key])) {
                $keys[] = $paramMap[$key];
            } else {
                $keys[] = $key;
            }
        }

        return array_combine($keys, array_values($params));
    }

    /**
     * @return bool
     */
    protected function isLazy()
    {
        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);

        return isset($options['lazy']) && $options['lazy'];
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType 0 to compare with false, 1 to compare with true
     * @param string                           $fieldName
     * @param mixed                            $data
     *
     * @return string
     */
    protected function buildExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $data
    ) {
        throw new \BadMethodCallException('Method buildExpr is not implemented');
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    protected function parseData($data)
    {
        if (\is_array($data)) {
            $data['type'] = \array_key_exists('type', $data)
                ? $this->normalizeType($data['type'])
                : null;
        }

        return $data;
    }

    /**
     * @param mixed $type
     *
     * @return mixed
     */
    protected function normalizeType($type)
    {
        if (!\is_int($type) && is_numeric($type)) {
            $type = (int)$type;
        }

        return $type;
    }

    /**
     * @param mixed $operator
     *
     * @return mixed
     */
    protected function getJoinOperator($operator)
    {
        return isset($this->joinOperators[$operator]) ? $this->joinOperators[$operator] : null;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param QueryBuilder $qb
     *
     * @return array [<dql>, <replacedAliases>]
     */
    protected function createDQLWithReplacedAliases(FilterDatasourceAdapterInterface $ds, QueryBuilder $qb)
    {
        $replacements = array_map(
            function ($alias) use ($ds) {
                return [
                    $alias,
                    $ds->generateParameterName($this->getName()),
                ];
            },
            DqlUtil::getAliases($qb->getDQL())
        );

        return [
            DqlUtil::replaceAliases($qb->getDQL(), $replacements),
            array_combine(array_column($replacements, 0), array_column($replacements, 1))
        ];
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     *
     * @return Expr\Join|null
     */
    protected function findRelatedJoin(FilterDatasourceAdapterInterface $ds)
    {
        return $this->findRelatedJoinByColumn($ds, $this->getOr(FilterUtility::DATA_NAME_KEY));
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $column
     * @return Expr\Join|null
     */
    protected function findRelatedJoinByColumn(FilterDatasourceAdapterInterface $ds, $column)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter || $this->isToOneColumn($ds, $column)) {
            return null;
        }

        [$alias] = explode('.', $column);

        return QueryBuilderUtil::findJoinByAlias($ds->getQueryBuilder(), $alias);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return array
     */
    protected function createConditionFieldExprs(QueryBuilder $qb)
    {
        $entities = $qb->getRootEntities();
        $idField = $qb
            ->getEntityManager()
            ->getClassMetadata(reset($entities))
            ->getSingleIdentifierFieldName();

        $rootAliases = $qb->getRootAliases();

        return [sprintf('%s.%s', reset($rootAliases), $idField)];
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return array
     */
    protected function getSelectFieldFromGroupBy(QueryBuilder $qb)
    {
        $groupBy = $qb->getDQLPart('groupBy');

        $expressions = [];
        foreach ($groupBy as $groupByPart) {
            foreach ($groupByPart->getParts() as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($qb, $part));
            }
        }

        $fields = [];
        foreach ($expressions as $expression) {
            $fields[] = QueryBuilderUtil::getSelectExprByAlias($qb, $expression) ?: $expression;
        }

        return $fields;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $groupByPart
     *
     * @return array
     */
    protected function getSelectFieldFromGroupByPart(QueryBuilder $qb, $groupByPart)
    {
        $expressions = [];
        if (strpos($groupByPart, ',') !== false) {
            $groupByParts = explode(',', $groupByPart);
            foreach ($groupByParts as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($qb, $part));
            }
        } else {
            $trimmedGroupByPart = trim($groupByPart);
            $expr = QueryBuilderUtil::getSelectExprByAlias($qb, $groupByPart);
            $expressions[] = $expr ?: $trimmedGroupByPart;
        }

        return $expressions;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     *
     * @return bool
     */
    protected function isToOne(FilterDatasourceAdapterInterface $ds): bool
    {
        return $this->isToOneColumn($ds, $this->getDataFieldName());
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $column
     * @return bool
     */
    protected function isToOneColumn(FilterDatasourceAdapterInterface $ds, $column): bool
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return false;
        }

        [$joinAlias] = explode('.', $column);

        return QueryBuilderUtil::isToOne($ds->getQueryBuilder(), $joinAlias);
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    protected function convertData($data)
    {
        if ($this->has(FilterUtility::VALUE_CONVERSION_KEY)) {
            if (($callback = $this->get(FilterUtility::VALUE_CONVERSION_KEY)) && is_callable($callback)) {
                return call_user_func($callback, $data);
            } else {
                throw new \BadFunctionCallException(
                    sprintf('\'%s\' is not callable', json_encode($callback))
                );
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getDataFieldName()
    {
        if (!$this->dataFieldName) {
            $this->dataFieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        }

        return $this->dataFieldName;
    }

    /**
     * @param string|int|null $type
     *
     * @return bool
     */
    protected function isValueRequired($type): bool
    {
        return FilterUtility::TYPE_EMPTY !== $type && FilterUtility::TYPE_NOT_EMPTY !== $type;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $comparisonExpr
     * @param $notExpression
     * @return mixed
     */
    protected function getExistsComparisonExpression(
        FilterDatasourceAdapterInterface $ds,
        string $comparisonExpr,
        $notExpression
    ) {
        $qb = $ds->getQueryBuilder();

        $fieldsExprs = $this->createConditionFieldExprs($qb);
        $subExprs = [];

        foreach ($fieldsExprs as $fieldExpr) {
            $subDql = $this->getSubQueryExpressionWithParameters($ds, $fieldExpr, $comparisonExpr)[0];

            $subExpr = $qb->expr()->exists($subDql);
            if ($notExpression) {
                $subExpr = $qb->expr()->not($subExpr);
            }
            $subExprs[] = $subExpr;
        }

        return $qb->expr()->andX(...$subExprs);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $data
     * @return bool
     */
    protected function shouldUseExists(FilterDatasourceAdapterInterface $ds, array $data): bool
    {
        // Exists is supported for OrmFilterDatasourceAdapter only
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return false;
        }

        // When grouping by CalendarDate is enabled filtering with EXISTS will behave incorrectly, because CalendarDate
        // became the root entity and EXISTS by sub-query will return a date id instead of entity id when there is
        // at least one entity that satisfies the filter.
        // Example of an incorrect query with a filter by order.status when sub-select is used:
        // SELECT order.identifier FROM CalendarDate cd LEFT JOIN Order order ... WHERE
        // EXISTS(SELECT cd1.id FROM CalendarDate cd1 LEFT JOIN Order order1 WHERE order1.status_id = 'open')
        if (in_array(CalendarDate::class, $ds->getQueryBuilder()->getRootEntities(), true)) {
            return false;
        }

        // Because of Doctrine bug https://github.com/doctrine/orm/issues/1845 GROUP BY does not work with functions.
        // So expression with alias should be present in the select and alias should be used in the GROUP BY
        // instead of the expression.
        // But, at the same time, exists sub-query can't contain more than one field selected. Because of this
        // exists cannot be used when there is grouping by some expression or function and having clause is present
        if ($this->containGroupByFunctionAndHaving($ds)) {
            return false;
        }

        return empty($data['in_group']) && $this->findRelatedJoin($ds);
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @return bool
     */
    protected function containGroupByFunctionAndHaving(
        OrmFilterDatasourceAdapter $ds
    ): bool {
        $qb = $ds->getQueryBuilder();
        if (!$qb->getDQLPart('having')) {
            return false;
        }

        foreach ($this->getSelectFieldFromGroupBy($qb) as $groupByField) {
            if (strpos($groupByField, '(') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param QueryBuilder $subQuery
     * @param string $fieldExpr
     */
    protected function processSubQueryExpressionGroupBy(
        OrmFilterDatasourceAdapter $ds,
        QueryBuilder $subQuery,
        string $fieldExpr
    ): void {
        // No need to add group by to sub-query if there is no additional having conditions applied
        if ($ds->getQueryBuilder()->getDQLPart('having')
            && $groupByFields = $this->getSelectFieldFromGroupBy($ds->getQueryBuilder())
        ) {
            $subQuery->addGroupBy(implode(', ', $groupByFields));
            $subQuery->addGroupBy($fieldExpr);
        }
    }
}
