<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;

interface DatagridInterface
{
    /**
     * Returns datagrid name
     *
     * @return string
     */
    public function getName();

    /**
     * Returns datagrid scope
     *
     * @return string
     */
    public function getScope();

    /**
     * Set scope of datagrid
     *
     * @param string $scope
     *
     * @return $this
     */
    public function setScope($scope);

    /**
     * Set grid datasource
     *
     * @param DatasourceInterface $source
     *
     * @return $this
     */
    public function setDatasource(DatasourceInterface $source);

    /**
     * Returns datasource object
     *
     * @return DatasourceInterface
     */
    public function getDatasource();

    /**
     * Returns datasource object accepted by extensions
     *
     * @return DatasourceInterface
     */
    public function getAcceptedDatasource();

    /**
     * Getter for acceptor object
     *
     * @return Acceptor
     */
    public function getAcceptor();

    /**
     * Setter for acceptor object
     *
     * @param Acceptor $acceptor
     *
     * @return $this
     */
    public function setAcceptor(Acceptor $acceptor);

    /**
     * Converts datasource into the result array
     * return ResultsObject
     *    'data' => converted source
     *    ....   => some additional info added by extensions
     * )
     *
     * @return ResultsObject
     */
    public function getData();

    /**
     * Retrieve metadata from all extensions
     * Metadata needed to create view layer (can be lazy - so additional request is required)
     *
     * @return MetadataObject
     */
    public function getMetadata();

    /**
     * Returns the same as getMetadata with resolved lazy stuff
     *
     * @return MetadataObject
     */
    public function getResolvedMetadata();

    /**
     * Returns parameters
     *
     * @return ParameterBag
     */
    public function getParameters();

    /**
     * Get config object
     *
     * @return DatagridConfiguration
     */
    public function getConfig();
}
