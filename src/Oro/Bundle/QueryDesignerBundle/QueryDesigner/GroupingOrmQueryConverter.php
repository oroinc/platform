<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
     * @param FunctionProviderInterface $functionProvider
     * @param ManagerRegistry           $doctrine
     */
    public function __construct(FunctionProviderInterface $functionProvider, ManagerRegistry $doctrine)
    {
        parent::__construct($functionProvider, $doctrine);
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
    protected function addJoinStatement($joinTableAlias, $joinFieldName, $joinAlias)
    {
        if ($this->isUnidirectionalJoin($joinAlias)) {
            $this->addUnidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);
        } else {
            $this->addBidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);
        }
    }

    /**
     * Checks if the given join alias represents unidirectional relationship
     *
     * @param string $joinAlias
     * @return bool
     */
    protected function isUnidirectionalJoin($joinAlias)
    {
        return 3 === count(
            explode(
                '::',
                $this->getJoinIdentifierLastPart($this->getJoinIdentifierByTableAlias($joinAlias))
            )
        );
    }

    /**
     * Builds JOIN condition for unidirectional relationship
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     * @return string
     */
    protected function getUnidirectionalJoinCondition($joinTableAlias, $joinFieldName, $joinAlias)
    {
        $joinParts       = explode(
            '::',
            $this->getJoinIdentifierLastPart($this->getJoinIdentifierByTableAlias($joinAlias))
        );
        $identifiers     = $this->getClassMetadata($joinParts[0])->getIdentifier();
        $targetFieldName = array_shift($identifiers);

        return sprintf(
            '%s.%s = %s.%s',
            $joinAlias,
            $joinFieldName,
            $joinTableAlias,
            $targetFieldName
        );
    }

    /**
     * Extracts entity name for unidirectional relationship
     *
     * @param string $joinAlias
     * @return string
     */
    protected function getUnidirectionalJoinEntity($joinAlias)
    {
        $joinParts       = explode(
            '::',
            $this->getJoinIdentifierLastPart($this->getJoinIdentifierByTableAlias($joinAlias))
        );

        return $joinParts[1];
    }

    /**
     * Performs conversion of unidirectional JOIN statement
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     */
    abstract protected function addUnidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);

    /**
     * Performs conversion of bidirectional JOIN statement
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     */
    abstract protected function addBidirectionalJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);

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
        $columnAlias,
        $filterName,
        array $filterData
    ) {
        $filter = [
            'column'     => sprintf('%s.%s', $tableAlias, $fieldName),
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
     * Get filter type for given field type
     *
     * @param string $fieldType
     * @return string
     */
    protected function getFilterType($fieldType)
    {
        switch ($fieldType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
            case 'decimal':
            case 'float':
            case 'money':
                return 'number';
            case 'percent':
                return 'percent';
            case 'boolean':
                return 'boolean';
            case 'date':
            case 'datetime':
                return 'datetime';
        }

        return 'string';
    }
}
