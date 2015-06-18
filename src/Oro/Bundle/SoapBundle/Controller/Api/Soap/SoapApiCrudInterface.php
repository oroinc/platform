<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Soap;

interface SoapApiCrudInterface extends SoapApiReadInterface
{
    /**
     * Create item.
     *
     * @return mixed The id of the created entity
     */
    public function handleCreateRequest();

    /**
     * Delete item.
     *
     * @param mixed $id The id of an entity to be deleted
     *
     * @return bool True if the entity has been successfully deleted; otherwise, false
     */
    public function handleDeleteRequest($id);

    /**
     * Update item.
     *
     * @param mixed $id The id of an entity to be updated
     *
     * @return bool True if the entity has been successfully updated; otherwise, false
     */
    public function handleUpdateRequest($id);
}
