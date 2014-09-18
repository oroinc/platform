<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;

class Datagrid implements DatagridInterface
{
    /** @var DatasourceInterface */
    protected $datasource;

    /** @var string */
    protected $name;

    /** @var DatagridConfiguration */
    protected $config;

    /** @var ParameterBag */
    protected $parameters;

    /** @var Acceptor */
    protected $acceptor;

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
    public function getData()
    {
        /** @var array $rows */
        $rows = $this->getAcceptedDatasource()->getResults();

        $result = ResultsObject::create(['data' => $rows]);
        $this->acceptor->acceptResult($result);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $data = MetadataObject::createNamed($this->getName(), []);
        $this->acceptor->acceptMetadata($data);

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function setDatasource(DatasourceInterface $source)
    {
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
        $this->acceptor->acceptDatasource($this->getDatasource());

        return $this->getDatasource();
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
