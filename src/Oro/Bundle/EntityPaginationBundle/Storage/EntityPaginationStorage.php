<?php

namespace Oro\Bundle\EntityPaginationBundle\Storage;

use Symfony\Component\HttpFoundation\Request;

class EntityPaginationStorage
{
    const STORAGE_NAME = 'entity_pagination_storage';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    public function addData($entityName, $gridName, array $paginationState)
    {
        $storage = $this->request->getSession()->get(self::STORAGE_NAME, []);
        $storage[$entityName]  = [
            'grid_name' => $gridName,
            'pagination_state' => $paginationState
        ];
        $this->request->getSession()->set(self::STORAGE_NAME, $storage);
    }

    public function getTotal($entity)
    {

    }

    public function getCurrent($entity)
    {

    }

    public function getPrevious($entity)
    {

    }

    public function getNext($entity)
    {

    }
}
