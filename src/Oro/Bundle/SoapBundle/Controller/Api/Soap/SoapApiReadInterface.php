<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Soap;

interface SoapApiReadInterface
{
    /**
     * Get item by identifier
     *
     * @param mixed $id The id of an entity
     *
     * @return object The entity object
     */
    public function handleGetRequest($id);

    /**
     * Get paginated items list.
     *
     * @param int        $page
     * @param int        $limit
     * @param array      $criteria array of filtering criteria, e.g. ['age' => 20, ...]
     *                             or \Doctrine\Common\Collections\Criteria
     * @param array|null $orderBy
     *
     * @return \Traversable The list of entities
     */
    public function handleGetListRequest($page, $limit, $criteria = [], $orderBy = null);
}
