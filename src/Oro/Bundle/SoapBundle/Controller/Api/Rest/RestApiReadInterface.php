<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

interface RestApiReadInterface
{
    /**
     * Get paginated items list
     *
     * @param int   $page
     * @param int   $limit
     * @param array $filters array of filtering criteria, e.g. ['age' => 20, ...]
     *                       or \Doctrine\Common\Collections\Criteria
     *
     * @return Response
     */
    public function handleGetListRequest($page, $limit, $filters = []);

    /**
     * Get item by identifier
     *
     * @param  mixed $id
     *
     * @return mixed
     */
    public function handleGetRequest($id);
}
