<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;

class DatagridConfigurationBuilderTestCase extends OrmQueryConverterTest
{
    /**
     * @param QueryDesignerModel                            $model
     * @param \PHPUnit\Framework\MockObject\MockObject|null $doctrine
     * @param \PHPUnit\Framework\MockObject\MockObject|null $functionProvider
     * @param \PHPUnit\Framework\MockObject\MockObject|null $virtualFieldProvider
     * @param array                                         $guessers
     *
     * @return DatagridConfigurationBuilder
     */
    protected function createDatagridConfigurationBuilder(
        QueryDesignerModel $model,
        $doctrine = null,
        $functionProvider = null,
        $virtualFieldProvider = null,
        array $guessers = [],
        $entityNameResolver = null
    ) {
        if (!isset($entityNameResolver)) {
            $entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        $builder = new DatagridConfigurationBuilder(
            $functionProvider ? : $this->getFunctionProvider(),
            $virtualFieldProvider ? : $this->getVirtualFieldProvider(),
            $doctrine ? : $this->getDoctrine(),
            new DatagridGuesserMock($guessers),
            $entityNameResolver
        );

        $builder->setGridName('test_grid');
        $builder->setSource($model);

        return $builder;
    }
}
