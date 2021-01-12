<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Symfony\Component\Form\Form;

/**
 * Represents a filter.
 */
interface FilterInterface
{
    /**
     * Initializes the filter.
     *
     * @param string $name
     * @param array  $params
     */
    public function init($name, array $params);

    /**
     * Returns the filter name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns a form to be used to validate and normalize the filter data.
     *
     * @return Form
     */
    public function getForm();

    /**
     * Returns the filter metadata.
     *
     * @return array
     */
    public function getMetadata();

    /**
     * Resolves lazy options.
     */
    public function resolveOptions();

    /**
     * Applies the filter restrictions to a data source.
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param mixed                            $data
     *
     * @return bool true if a filter successfully applied; otherwise, false.
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data);

    /**
     * Sets a state of the filter.
     *
     * @param $state
     *
     * @return $this
     */
    public function setFilterState($state);

    /**
     * Returns a state of the filter.
     *
     * @return mixed
     */
    public function getFilterState();
}
