<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as BaseApiEntityManager;

/**
 * API entity manager for notes with strict entity retrieval.
 *
 * This manager extends the base API entity manager to provide note-specific functionality.
 * It overrides the find method to throw an {@see EntityNotFoundException} when a note with the
 * specified ID is not found, ensuring that API consumers receive proper error responses
 * instead of null values. This strict behavior is appropriate for REST API endpoints where
 * missing resources should result in 404 responses.
 */
class ApiEntityManager extends BaseApiEntityManager
{
    #[\Override]
    public function find($id)
    {
        /** @var Note $result */
        $result = parent::find($id);
        if (!$result) {
            throw new EntityNotFoundException();
        }

        return $result;
    }
}
