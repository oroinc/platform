<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

/**
 * Defines the contract for handling autocomplete search operations.
 *
 * Implementations of this interface provide search functionality for autocomplete fields,
 * including querying entities, converting results to view format, and exposing searchable
 * properties and entity information to the UI layer.
 */
interface SearchHandlerInterface extends ConverterInterface
{
    /**
     * Gets search results, that includes found items and any additional information.
     *
     * @param string $query
     * @param int $page
     * @param int $perPage
     * @param bool $searchById
     * @return array
     */
    public function search($query, $page, $perPage, $searchById = false);

    /**
     * Gets properties that should be displayed
     *
     * @return array
     */
    public function getProperties();

    /**
     * Gets entity name that is handled by search
     *
     * @return mixed
     */
    public function getEntityName();
}
