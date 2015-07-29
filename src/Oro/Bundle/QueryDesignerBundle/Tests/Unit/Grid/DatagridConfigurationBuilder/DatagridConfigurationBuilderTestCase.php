<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;

class DatagridConfigurationBuilderTestCase extends OrmQueryConverterTest
{
    /**
     * @param QueryDesignerModel                            $model
     * @param \PHPUnit_Framework_MockObject_MockObject|null $doctrine
     * @param \PHPUnit_Framework_MockObject_MockObject|null $functionProvider
     * @param \PHPUnit_Framework_MockObject_MockObject|null $virtualFieldProvider
     * @param array                                         $guessers
     *
     * @return DatagridConfigurationBuilder
     */
    protected function createDatagridConfigurationBuilder(
        QueryDesignerModel $model,
        $doctrine = null,
        $functionProvider = null,
        $virtualFieldProvider = null,
        array $guessers = []
    ) {
        $builder = new DatagridConfigurationBuilder(
            $functionProvider ? : $this->getFunctionProvider(),
            $virtualFieldProvider ? : $this->getVirtualFieldProvider(),
            $doctrine ? : $this->getDoctrine(),
            new DatagridGuesserMock($guessers)
        );

        $builder->setGridName('test_grid');
        $builder->setSource($model);

        return $builder;
    }
}
