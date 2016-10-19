<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\ApiBundle\Filter\PrimaryFieldFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;

class PrimaryFieldFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The Field must not be empty.
     */
    public function testFieldIsNotSpecified()
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->apply(new Criteria(), new FilterValue('path', 'value', PrimaryFieldFilter::EQ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The DataField must not be empty.
     */
    public function testDataFieldIsNotSpecified()
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->apply(new Criteria(), new FilterValue('path', 'value', PrimaryFieldFilter::EQ));
    }

    public function testOptions()
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setDataField('dataField');
        $filter->setPrimaryFlagField('primaryFlagField');

        $this->assertEquals('dataField', $filter->getDataField());
        $this->assertEquals('primaryFlagField', $filter->getPrimaryFlagField());
    }

    public function testApplyNullValue()
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->setDataField('dataField');

        $criteria = new Criteria();
        $filter->apply($criteria, null);

        $this->assertNull($criteria->getWhereExpression());
    }

    public function testApplyWithDefaultPrimaryFlagField()
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->setDataField('dataField');

        $criteria = new Criteria();
        $filter->apply($criteria, new FilterValue('path', 'value', PrimaryFieldFilter::EQ));

        $this->assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('association.dataField', Comparison::EQ, 'value'),
                    new Comparison('association.primary', Comparison::EQ, true),
                ]
            ),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyWithCustomPrimaryFlagField()
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->setDataField('dataField');
        $filter->setPrimaryFlagField('primaryFlagField');

        $criteria = new Criteria();
        $filter->apply($criteria, new FilterValue('path', 'value', PrimaryFieldFilter::EQ));

        $this->assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('association.dataField', Comparison::EQ, 'value'),
                    new Comparison('association.primaryFlagField', Comparison::EQ, true),
                ]
            ),
            $criteria->getWhereExpression()
        );
    }
}
