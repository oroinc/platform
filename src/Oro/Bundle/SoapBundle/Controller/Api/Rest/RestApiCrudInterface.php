<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

interface RestApiCrudInterface extends RestApiReadInterface
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    /**
     * Create item.
     *
     * @return Response
     */
    public function handleCreateRequest();

    /**
     * Update item.
     *
     * @param  mixed $id
     *
     * @return Response
     */
    public function handleUpdateRequest($id);

    /**
     * Delete item.
     *
     * @param  mixed $id
     *
     * @return Response
     */
    public function handleDeleteRequest($id);
}
