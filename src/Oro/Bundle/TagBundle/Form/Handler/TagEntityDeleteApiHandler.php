<?php

namespace Oro\Bundle\TagBundle\Form\Handler;

use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;

class TagEntityDeleteApiHandler extends DeleteHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TagManager */
    protected $tagManager;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param TagManager $tagManager
     */
    public function setTagManager(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * Handle delete entity object.
     *
     * @param RelationIdentifier $id
     * @param ApiEntityManager   $manager
     *
     * @throws EntityNotFoundException if an entity with the given id does not exist
     * @throws ForbiddenException if a delete operation is forbidden
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        $em = $manager->getObjectManager();

        /** @var Tag $tag */
        $tag = $this->tagManager->loadOrCreateTag($id->getOwnerEntityId());
        if (!$tag->getId()) {
            throw new EntityNotFoundException();
        }
        if (!$this->securityFacade->isGranted('EDIT', $tag)) {
            throw new ForbiddenException('has no edit permissions for tag entity');
        }

        $entity = $em->find($id->getTargetEntityClass(), $id->getTargetEntityId());
        if (!$entity) {
            throw new EntityNotFoundException();
        }
        if (!$this->securityFacade->isGranted('VIEW', $entity)) {
            throw new ForbiddenException('has no view permissions for target entity');
        }
        if (!$this->tagManager->isTaggable($entity)) {
            // @todo: Change exception/message.
            throw new \InvalidArgumentException('Entity should be taggable');
        }

        $this->tagManager->deleteTagging($entity, [$tag]);
    }
}
