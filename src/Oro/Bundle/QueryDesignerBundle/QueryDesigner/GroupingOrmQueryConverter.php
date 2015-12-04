<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

abstract class GroupingOrmQueryConverter extends AbstractOrmQueryConverter
{
    /** @var array */
    protected $filters = [];

    /** @var PropertyAccessor */
    protected $accessor;

    /** string */
    protected $currentFilterPath;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry               $doctrine
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $doctrine);
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    protected function doConvert(AbstractQueryDesigner $source)
    {
        $this->filters           = [];
        $this->currentFilterPath = '';
        parent::doConvert($source);
        $this->filters           = null;
        $this->currentFilterPath = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function beginWhereGroup()
    {
        $this->currentFilterPath .= '[0]';
        $this->accessor->setValue($this->filters, $this->currentFilterPath, []);
    }

    /**
     * {@inheritdoc}
     */
    protected function endWhereGroup()
    {
        $this->currentFilterPath = substr(
            $this->currentFilterPath,
            0,
            strrpos($this->currentFilterPath, '[')
        );
        if ($this->currentFilterPath !== '') {
            $this->incrementCurrentFilterPath();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereOperator($operator)
    {
        $this->accessor->setValue($this->filters, $this->currentFilterPath, $operator);
        $this->incrementCurrentFilterPath();
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereCondition(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $filterName,
        array $filterData,
        $functionExpr = null
    ) {
        $filter = [
            'column'     => $this->getFilterByExpr(
                $entityClassName,
                $tableAlias,
                $fieldName,
                $functionExpr
                ? $this->prepareFunctionExpression(
                    $functionExpr,
                    $tableAlias,
                    $fieldName,
                    $columnExpr,
                    $columnAlias
                )
                : $columnExpr
            ),
            'filter'     => $filterName,
            'filterData' => $filterData
        ];
        if ($columnAlias) {
            $filter['columnAlias'] = $columnAlias;
        }
        $this->accessor->setValue($this->filters, $this->currentFilterPath, $filter);
        $this->incrementCurrentFilterPath();
    }

    /**
     * Increments last index in the path of filter
     */
    protected function incrementCurrentFilterPath()
    {
        $start                   = strrpos($this->currentFilterPath, '[');
        $index                   = substr(
            $this->currentFilterPath,
            $start + 1,
            strlen($this->currentFilterPath) - $start - 2
        );
        $this->currentFilterPath = sprintf(
            '%s%d]',
            substr($this->currentFilterPath, 0, $start + 1),
            intval($index) + 1
        );
    }

    /**
     * @param string $entityClassName
     * @param string $tableAlias
     * @param string $fieldName
     * @param string $columnExpr
     *
     * @return string
     */
    protected function getFilterByExpr(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr
    ) {
        $filterById = false;
        if ($entityClassName && $this->virtualFieldProvider->isVirtualField($entityClassName, $fieldName)) {
            $key = sprintf('%s::%s', $entityClassName, $fieldName);
            if (isset($this->virtualColumnOptions[$key]['filter_by_id'])) {
                if ($this->virtualColumnOptions[$key]['filter_by_id']) {
                    $filterById = true;
                };
            }
        }

        return $filterById
            ? sprintf('%s.%s', $tableAlias, $fieldName)
            : $columnExpr;
    }
}
