<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class EmailStringFilter extends StringFilter
{
    /** @var Expr\Comparison|Expr\Comparison[]|null */
    protected $expression = null;

    /** @var Parameter[]|null */
    protected $parameters = null;

    /**
     * @return Expr\Comparison|Expr\Comparison[]|null
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @return Parameter[]|null
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        /** @var $ds OrmFilterDatasourceAdapter $data */

        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $sourceParametersCollection = clone $ds->getQueryBuilder()->getParameters();
        $sourceParameters = $sourceParametersCollection->toArray();

        $this->expression = $this->buildExpr($ds, $data['type'], $this->getDataFieldName(), $data);
        $this->parameters = array_diff(
            $ds->getQueryBuilder()->getParameters()->toArray(),
            $sourceParameters
        );

        $ds->getQueryBuilder()->setParameters($sourceParametersCollection);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return TextFilterType::NAME;
    }
}
