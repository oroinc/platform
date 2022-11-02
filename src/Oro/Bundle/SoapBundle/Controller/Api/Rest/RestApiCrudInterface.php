<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

/**
 * Represents a controller for CRUD actions.
 */
interface RestApiCrudInterface extends RestApiReadInterface
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    /**
     * Handles CREATE request for a single item
     *
     * @return Response
     */
    public function handleCreateRequest();

    /**
     * Handles UPDATE request for a single item
     *
     * @param mixed $id The id of an entity to be updated
     *
     * @return Response
     */
    public function handleUpdateRequest($id);

    /**
     * Handles DELETE request for a single item
     *
     * @param mixed $id      The id of an entity to be deleted
     * @param array $options The options for the delete operation
     *
     * @return Response
     */
    public function handleDeleteRequest($id, array $options = []);
}
