<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareInterface;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

/**
 * Represents datagrid.
 * Provides methods to work with data source, configuration, metadata and data.
 */
class Datagrid implements DatagridInterface, MemoryCacheProviderAwareInterface
{
    /** @var string */
    protected $name;

    /** @var DatagridConfiguration */
    protected $config;

    /** @var ParameterBag */
    protected $parameters;

    /** @var MemoryCacheProviderInterface|null */
    protected $memoryCacheProvider;

    /** @var string */
    protected $scope;

    /** @var DatasourceInterface */
    protected $datasource;

    /** @var Acceptor */
    protected $acceptor;

    /** @var MetadataObject|null */
    protected $metadata;

    public function __construct(string $name, DatagridConfiguration $config, ParameterBag $parameters)
    {
        $this->name = $name;
        $this->config = $config;
        $this->parameters = $parameters;

        $this->initialize();
    }

    #[\Override]
    public function setMemoryCacheProvider(?MemoryCacheProviderInterface $memoryCacheProvider): void
    {
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    /**
     * Performs an initialization of a data grid.
     * You can override this method to perform modifications of grid configuration
     * based on grid parameters.
     */
    public function initialize()
    {
    }

    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    #[\Override]
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    #[\Override]
    public function getScope()
    {
        return $this->scope;
    }

    #[\Override]
    public function getData()
    {
        if (null === $this->memoryCacheProvider) {
            return $this->loadData();
        }

        return $this->memoryCacheProvider->get(
            ['datagrid_results' => $this->getParameters()],
            function () {
                return $this->loadData();
            }
        );
    }

    #[\Override]
    public function getMetadata()
    {
        if (null === $this->metadata) {
            $this->metadata = MetadataObject::createNamed($this->getName(), []);
            $this->acceptor->acceptMetadata($this->metadata);
        }

        return $this->metadata;
    }

    #[\Override]
    public function getResolvedMetadata()
    {
        $data = MetadataObject::createNamed($this->getName(), [MetadataObject::LAZY_KEY => false]);
        $this->acceptor->acceptMetadata($data);

        return $data;
    }

    #[\Override]
    public function setDatasource(DatasourceInterface $source)
    {
        $this->memoryCacheProvider?->reset();
        $this->datasource = $source;

        return $this;
    }

    #[\Override]
    public function getDatasource()
    {
        return $this->datasource;
    }

    #[\Override]
    public function getAcceptedDatasource()
    {
        $this->acceptDatasource();

        return $this->getDatasource();
    }

    #[\Override]
    public function acceptDatasource()
    {
        $this->acceptor->acceptDatasource($this->getDatasource());

        return $this;
    }

    #[\Override]
    public function getAcceptor()
    {
        return $this->acceptor;
    }

    #[\Override]
    public function setAcceptor(Acceptor $acceptor)
    {
        $this->acceptor = $acceptor;

        return $this;
    }

    #[\Override]
    public function getParameters()
    {
        return $this->parameters;
    }

    #[\Override]
    public function getConfig()
    {
        return $this->config;
    }

    protected function loadData(): ResultsObject
    {
        $rows = $this->getAcceptedDatasource()->getResults();
        $results = ResultsObject::create(
            ['data' => $rows],
            PropertyAccess::createPropertyAccessorWithDotSyntax()
        );
        $this->acceptor->acceptResult($results);

        return $results;
    }
}
