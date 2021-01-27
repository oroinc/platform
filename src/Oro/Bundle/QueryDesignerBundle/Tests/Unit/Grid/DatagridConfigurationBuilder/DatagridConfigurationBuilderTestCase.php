<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\ColumnOptionsGuesserMock;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTestCase;

class DatagridConfigurationBuilderTestCase extends OrmQueryConverterTestCase
{
    /**
     * @param AbstractQueryDesigner                 $source
     * @param ManagerRegistry|null                  $doctrine
     * @param FunctionProviderInterface|null        $functionProvider
     * @param VirtualFieldProviderInterface|null    $virtualFieldProvider
     * @param VirtualRelationProviderInterface|null $virtualRelationProvider
     * @param ColumnOptionsGuesserInterface[]       $guessers
     * @param EntityNameResolver|null               $entityNameResolver
     *
     * @return DatagridConfigurationBuilder
     */
    protected function createDatagridConfigurationBuilder(
        AbstractQueryDesigner $source,
        ManagerRegistry $doctrine = null,
        FunctionProviderInterface $functionProvider = null,
        VirtualFieldProviderInterface $virtualFieldProvider = null,
        VirtualRelationProviderInterface $virtualRelationProvider = null,
        array $guessers = [],
        EntityNameResolver $entityNameResolver = null
    ): DatagridConfigurationBuilder {
        $builder = new DatagridConfigurationBuilder(
            $functionProvider ?? $this->getFunctionProvider(),
            $virtualFieldProvider ?? $this->getVirtualFieldProvider(),
            $virtualRelationProvider ?? $this->getVirtualRelationProvider(),
            new DoctrineHelper($doctrine ?? $this->getDoctrine()),
            new DatagridGuesser($guessers ?: [new ColumnOptionsGuesserMock()]),
            $entityNameResolver ?? $this->getEntityNameResolver()
        );
        $builder->setGridName('test_grid');
        $builder->setSource($source);

        return $builder;
    }
}
