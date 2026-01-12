<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the contract for REST API read operations.
 *
 * Provides methods for handling GET requests to retrieve single entities and lists of entities
 * with support for pagination, filtering, and custom join associations.
 */
interface RestApiReadInterface
{
    public const ACTION_LIST = 'list';
    public const ACTION_READ = 'read';

    /**
     * Handles GET request for the list of items
     *
     * @param int   $page
     * @param int   $limit
     * @param array $filters array of filtering criteria, e.g. ['age' => 20, ...]
     *                       or \Doctrine\Common\Collections\Criteria
     * @param array $joins   Additional associations required to filter data
     *
     * @return Response
     */
    public function handleGetListRequest($page, $limit, $filters = [], $joins = []);

    /**
     * Handles GET request for a single item
     *
     * @param mixed $id The id of an entity
     *
     * @return Response
     */
    public function handleGetRequest($id);
}
