<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Handles the removal of any entities and removes Files which have been linked to these entities.
 */
class EntityDeleteListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onFlush(): void
    {
        $manager = $this->doctrineHelper->getEntityManagerForClass(File::class);
        $uow = $manager->getUnitOfWork();
        $repository = $manager->getRepository(File::class);

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $id = $this->doctrineHelper->getSingleEntityIdentifier($entity, false);
            if (!$id || !\is_numeric($id)) {
                continue;
            }

            $files = $repository->findBy(
                [
                    'parentEntityClass' => $this->doctrineHelper->getEntityClass($entity),
                    'parentEntityId' => $id,
                ]
            );

            foreach ($files as $file) {
                $manager->remove($file);
            }
        }
    }
}
