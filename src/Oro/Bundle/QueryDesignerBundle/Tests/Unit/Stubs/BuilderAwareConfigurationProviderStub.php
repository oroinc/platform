<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\BuilderAwareInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;

class BuilderAwareConfigurationProviderStub implements ConfigurationProviderInterface, BuilderAwareInterface
{
    /** @var DatagridConfigurationBuilder */
    private $builder;

    public function __construct(DatagridConfigurationBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(string $gridName): bool
    {
        throw new \BadMethodCallException('not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(string $gridName): DatagridConfiguration
    {
        throw new \BadMethodCallException('not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getBuilder(): DatagridConfigurationBuilder
    {
        return $this->builder;
    }
}
