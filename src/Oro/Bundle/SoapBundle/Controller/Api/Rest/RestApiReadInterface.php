<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

interface RestApiReadInterface
{
    const ACTION_LIST = 'list';
    const ACTION_READ = 'read';

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
