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

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onFlush(): void
    {
        $manager = $this->doctrineHelper->getEntityManagerForClass(File::class);
        $uow = $manager->getUnitOfWork();
        $repository = $manager->getRepository(File::class);

        $idsByClassName = [];
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $id = $this->doctrineHelper->getSingleEntityIdentifier($entity, false);
            if (!$id || !\is_numeric($id)) {
                continue;
            }

            $idsByClassName[$this->doctrineHelper->getEntityClass($entity)][] = $id;
        }

        foreach ($idsByClassName as $className => $ids) {
            $files = $repository->findBy(
                [
                    'parentEntityClass' => $className,
                    'parentEntityId' => $ids,
                ]
            );

            foreach ($files as $file) {
                $manager->remove($file);
            }
        }
    }
}
