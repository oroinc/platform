<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Represents a filter.
 */
interface FilterInterface extends ResetInterface
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
     * @return FormInterface
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
     * Prepares data to be ready to pass to {@see apply()} method.
     * This method does a filter value normalization the similar as an appropriate filter form,
     * but without data validation.
     * This method is used instead of the form when the data are already valid,
     * e.g. when loading a segment or a report data.
     *
     * @throw \Throwable if a filter value normalization failed
     */
    public function prepareData(array $data): array;

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
