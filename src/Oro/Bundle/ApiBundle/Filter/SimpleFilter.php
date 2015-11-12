<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

class SimpleFilter extends StandaloneFilter
{
    /** @var string */
    protected $fieldPath;

    /**
     * @param string     $fieldPath
     * @param string     $dataType
     * @param string     $description
     * @param mixed|null $defaultValue
     */
    public function __construct($fieldPath, $dataType, $description, $defaultValue = null)
    {
        parent::__construct($dataType, $description, $defaultValue);
        $this->fieldPath = $fieldPath;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null)
    {
        if (null !== $value) {
            $operator = $value->getOperator();
            $val      = $value->getValue();
        } else {
            $operator = null;
            $val      = $this->getDefaultValue();
            if (null !== $val) {
                $operator = '=';
            }
        }
        if (null !== $operator) {
            $this->doApply($criteria, $operator, $val);
        }
    }

    /**
     * @param Criteria $criteria
     * @param string   $operator
     * @param mixed    $value
     */
    protected function doApply(Criteria $criteria, $operator, $value)
    {
        if (null === $value) {
            return;
        }

        $exprBuilder = Criteria::expr();
        switch ($operator) {
            case '>':
                $expr = $exprBuilder->gt($this->fieldPath, $value);
                break;
            case '<':
                $expr = $exprBuilder->lt($this->fieldPath, $value);
                break;
            case '>=':
                $expr = $exprBuilder->gte($this->fieldPath, $value);
                break;
            case '<=':
                $expr = $exprBuilder->lte($this->fieldPath, $value);
                break;
            case '<>':
                if (is_array($value)) {
                    $expr = $exprBuilder->notIn($this->fieldPath, $value);
                } else {
                    $expr = $exprBuilder->neq($this->fieldPath, $value);
                }
                break;
            case '=':
            default:
                if (is_array($value)) {
                    $expr = $exprBuilder->in($this->fieldPath, $value);
                } else {
                    $expr = $exprBuilder->eq($this->fieldPath, $value);
                }
                break;
        }

        $criteria->andWhere($expr);
    }
}
