<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\Query\Expr as Expr;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @todo refactor in BAP-11688
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
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $joinOperator = $this->getJoinOperator($data['type']);
        $relatedJoin = $this->findRelatedJoin($ds);
        $type = ($joinOperator && $relatedJoin) ? $joinOperator : $data['type'];
        $comparisonExpr = $this->buildExpr($ds, $type, $this->get(FilterUtility::DATA_NAME_KEY), $data);
        if (!$comparisonExpr) {
            return true;
        }

        if ($relatedJoin) {
            $qb = $ds->getQueryBuilder();

            $fieldsExprs = $this->createConditionFieldExprs($qb);
            $subExprs = [];
            foreach ($fieldsExprs as $fieldExpr) {
                $subQb = clone $qb;
                $subQb
                    ->resetDqlPart('orderBy')
                    ->select($fieldExpr)
                    ->andWhere($comparisonExpr)
                    ->andWhere(sprintf('%1$s = %1$s', $fieldExpr));
                $groupBy = implode(', ', $this->getSelectFieldFromGroupBy($qb));
                if ($groupBy) {
                    // replace aliases from SELECT by expressions, since SELECT clause is changed
                    $subQb->groupBy($groupBy);
                }
                list($dql, $replacements) = $this->createDQLWithReplacedAliases($ds, $subQb);
                list($fieldAlias, $field) = explode('.', $fieldExpr);
                $replacedFieldExpr = sprintf('%s.%s', $replacements[$fieldAlias], $field);
                $oldExpr = sprintf('%1$s = %1$s', $replacedFieldExpr);
                $newExpr = sprintf('%s = %s', $replacedFieldExpr, $fieldExpr);
                $dql = strtr($dql, [$oldExpr => $newExpr]);

                $subExpr = $qb->expr()->exists($dql);
                if ($joinOperator) {
                    $subExpr = $qb->expr()->not($subExpr);
                }
                $subExprs[] = $subExpr;
            }
            $this->applyFilterToClause($ds, call_user_func_array([$qb->expr(), 'andX'], $subExprs));
        } else {
            $this->applyFilterToClause($ds, $comparisonExpr);
        }

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
     * @return array|bool
     */
    protected function parseData($data)
    {
        return $data;
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
     * @return [$dql, $replacedAliases]
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
            QueryUtils::getDqlAliases($qb->getDQL())
        );

        return [
            QueryUtils::replaceDqlAliases($qb->getDQL(), $replacements),
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
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return null;
        }

        list($alias) = explode('.', $this->getOr(FilterUtility::DATA_NAME_KEY));

        return QueryUtils::findJoinByAlias($ds->getQueryBuilder(), $alias);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return array
     */
    protected function createConditionFieldExprs(QueryBuilder $qb)
    {
        $groupByFields = $this->getSelectFieldFromGroupBy($qb);
        if ($groupByFields) {
            return $groupByFields;
        }

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
            $fields[] = QueryUtils::getSelectExprByAlias($qb, $expression) ?: $expression;
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
            $expr = QueryUtils::getSelectExprByAlias($qb, $groupByPart);
            $expressions[] = $expr ?: $trimmedGroupByPart;
        }

        return $expressions;
    }
}
