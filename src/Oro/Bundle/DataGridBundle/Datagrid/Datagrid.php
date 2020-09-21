<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareInterface;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;

/**
 * Represents datagrid.
 * Provides methods to work with data source, configuration, metadata and data.
 */
class Datagrid implements DatagridInterface, MemoryCacheProviderAwareInterface
{
    use MemoryCacheProviderAwareTrait;

    /** @var DatasourceInterface */
    protected $datasource;

    /** @var string */
    protected $name;

    /** @var string */
    protected $scope;

    /** @var DatagridConfiguration */
    protected $config;

    /** @var ParameterBag */
    protected $parameters;

    /** @var Acceptor */
    protected $acceptor;

    /** @var MetadataObject|null */
    protected $metadata;

    /**
     * @param string                $name
     * @param DatagridConfiguration $config
     * @param ParameterBag          $parameters
     */
    public function __construct($name, DatagridConfiguration $config, ParameterBag $parameters)
    {
        $this->name       = $name;
        $this->config     = $config;
        $this->parameters = $parameters;

        $this->initialize();
    }

    /**
     * Performs an initialization of a data grid.
     * You can override this method to perform modifications of grid configuration
     * based on grid parameters.
     */
    public function initialize()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return $this->getMemoryCacheProvider()->get(
            ['datagrid_results' => $this->getParameters()],
            function () {
                $rows = $this->getAcceptedDatasource()->getResults();
                $results = ResultsObject::create(['data' => $rows]);
                $this->acceptor->acceptResult($results);

                return $results;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        if (null === $this->metadata) {
            $this->metadata = MetadataObject::createNamed($this->getName(), []);
            $this->acceptor->acceptMetadata($this->metadata);
        }

        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getResolvedMetadata()
    {
        $data = MetadataObject::createNamed($this->getName(), [MetadataObject::LAZY_KEY => false]);
        $this->acceptor->acceptMetadata($data);

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function setDatasource(DatasourceInterface $source)
    {
        $this->getMemoryCacheProvider()->reset();
        $this->datasource = $source;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * {@inheritDoc}
     */
    public function getAcceptedDatasource()
    {
        $this->acceptDatasource();

        return $this->getDatasource();
    }

    /**
     * {@inheritDoc}
     */
    public function acceptDatasource()
    {
        $this->acceptor->acceptDatasource($this->getDatasource());

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAcceptor()
    {
        return $this->acceptor;
    }

    /**
     * {@inheritDoc}
     */
    public function setAcceptor(Acceptor $acceptor)
    {
        $this->acceptor = $acceptor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return $this->config;
    }
}
