<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * The base class for filters.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractFilter implements FilterInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var FilterUtility */
    protected $util;

    /** @var array */
    protected $joinOperators = [];

    /** @var string */
    protected $name;

    /** @var array */
    protected $params;

    /** @var array */
    protected $unresolvedOptions = [];

    /** @var array [array, ...] */
    protected $additionalOptions = [];

    /** @var array */
    protected $state;

    /** @var string */
    protected $dataFieldName;

    private ?FormInterface $form = null;
    private ?FormView $formView = null;

    public function __construct(FormFactoryInterface $factory, FilterUtility $util)
    {
        $this->formFactory = $factory;
        $this->util = $util;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->name = null;
        $this->params = null;
        $this->unresolvedOptions = [];
        $this->additionalOptions = [];
        $this->state = null;
        $this->dataFieldName = null;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $this->name = $name;
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
        if (null === $this->form) {
            $this->form = $this->createForm();
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
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $formView = $this->getFormView();
        $typeView = $formView->children['type'];

        $defaultMetadata = [
            'name'    => $this->getName(),
            // use filter name if label not set
            'label'   => $this->name ? ucfirst($this->name) : '',
            'choices' => $typeView->vars['choices'],
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function setFilterState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilterState()
    {
        return $this->state;
    }

    /**
     * Returns form type associated to this filter
     *
     * @return string
     */
    abstract protected function getFormType();

    protected function createForm(): FormInterface
    {
        return $this->formFactory->create(
            $this->getFormType(),
            [],
            array_merge($this->getOr(FilterUtility::FORM_OPTIONS_KEY, []), ['csrf_protection' => false])
        );
    }

    public function getFormView(): FormView
    {
        if (null === $this->formView) {
            $this->formView = $this->getForm()->createView();
        }

        return $this->formView;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string                     $fieldExpr
     * @param string|array|null          $filter
     *
     * @return array
     */
    protected function getSubQueryExpressionWithParameters(
        OrmFilterDatasourceAdapter $ds,
        $fieldExpr,
        $filter
    ): array {
        return FilterOrmQueryUtil::getSubQueryExpressionWithParameters(
            $ds,
            $this->createSubQueryBuilder($ds, $filter),
            $fieldExpr,
            $this->getName()
        );
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param mixed|null                 $filter
     *
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
     * Applies a filter expression to having or where clause depending on configuration.
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
     * @param string|null $paramName
     *
     * @return mixed
     */
    protected function get($paramName = null)
    {
        if (null === $paramName) {
            return $this->params;
        }

        if (!isset($this->params[$paramName])) {
            throw new \LogicException(sprintf('Trying to access not existing parameter: "%s"', $paramName));
        }

        return $this->params[$paramName];
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
     * @param string|null $paramName
     * @param mixed       $default
     *
     * @return mixed
     */
    protected function getOr($paramName = null, $default = null)
    {
        if (null === $paramName) {
            return $this->params;
        }

        return $this->params[$paramName] ?? $default;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function mapParams($params)
    {
        $keys = [];
        $paramMap = $this->util->getParamMap();
        foreach (array_keys($params) as $key) {
            $keys[] = $paramMap[$key] ?? $key;
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
        return $this->joinOperators[$operator] ?? null;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param QueryBuilder               $qb
     *
     * @return array [DQL, replaced aliases]
     */
    protected function createDqlWithReplacedAliases(OrmFilterDatasourceAdapter $ds, QueryBuilder $qb)
    {
        return FilterOrmQueryUtil::createDqlWithReplacedAliases($ds, $qb, $this->getName());
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     *
     * @return Expr\Join|null
     */
    protected function findRelatedJoin(FilterDatasourceAdapterInterface $ds)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return null;
        }

        return FilterOrmQueryUtil::findRelatedJoinByColumn($ds, $this->getOr(FilterUtility::DATA_NAME_KEY));
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return array
     */
    protected function createConditionFieldExprs(QueryBuilder $qb)
    {
        return [FilterOrmQueryUtil::getSingleIdentifierFieldExpr($qb)];
    }

    protected function isToOne(FilterDatasourceAdapterInterface $ds): bool
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return false;
        }

        return FilterOrmQueryUtil::isToOneColumn($ds, $this->getDataFieldName());
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    protected function convertData($data)
    {
        if (!$this->has(FilterUtility::VALUE_CONVERSION_KEY)) {
            return $data;
        }

        $callback = $this->get(FilterUtility::VALUE_CONVERSION_KEY);
        if ($callback && is_callable($callback)) {
            return call_user_func($callback, $data);
        }

        throw new \BadFunctionCallException(sprintf('"%s" is not callable', json_encode($callback)));
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
        if (FilterOrmQueryUtil::containGroupByFunctionAndHaving($ds)) {
            return false;
        }

        return empty($data['in_group']) && $this->findRelatedJoin($ds);
    }
}
