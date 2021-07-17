<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The handler that is used by the old REST API to delete entities.
 */
class DeleteHandler
{
    /** @var EntityDeleteHandlerRegistry */
    private $deleteHandlerRegistry;

    public function __construct(EntityDeleteHandlerRegistry $deleteHandlerRegistry)
    {
        $this->deleteHandlerRegistry = $deleteHandlerRegistry;
    }

    /**
     * Deletes an entity with the given ID.
     *
     * @param mixed            $id
     * @param ApiEntityManager $manager
     * @param array            $options
     *
     * @throws EntityNotFoundException if an entity with the given id does not exist
     * @throws AccessDeniedException if the delete operation is forbidden
     */
    public function handleDelete($id, ApiEntityManager $manager, array $options = []): void
    {
        $entity = $manager->find($id);
        if (!$entity) {
            throw new EntityNotFoundException();
        }

        $this->deleteHandlerRegistry
            ->getHandler($manager->getClass())
            ->delete($entity, true, $options);
    }
}
