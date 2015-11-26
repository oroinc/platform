<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TagBundle\Entity\Repository\TagRepository;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TagManager
{
    const ACL_RESOURCE_REMOVE_ID_KEY = 'oro_tag_unassign_global';
    const ACL_RESOURCE_CREATE_ID_KEY = 'oro_tag_create';
    const ACL_RESOURCE_ASSIGN_ID_KEY = 'oro_tag_assign_unassign';

    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $tagClass;

    /** @var string */
    protected $taggingClass;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var Router */
    protected $router;

    protected $storage = [];

    /**
     * @param EntityManager  $em
     * @param string         $tagClass     - FQCN
     * @param string         $taggingClass - FQCN
     * @param SecurityFacade $securityFacade
     * @param Router         $router
     */
    public function __construct(
        EntityManager $em,
        $tagClass,
        $taggingClass,
        SecurityFacade $securityFacade,
        Router $router
    ) {
        $this->em             = $em;
        $this->tagClass       = $tagClass;
        $this->taggingClass   = $taggingClass;
        $this->securityFacade = $securityFacade;
        $this->router         = $router;
    }

    /**
     * @param @param string $entityClassName The FQCN of the entity
     * @param array $ids
     *
     * @return array [id, name, entityId]
     */
    public function getTagsByEntityIds($entityClassName, array $ids)
    {
        $repository = $this->getTagsRepository();

        return $repository->getTagsByEntityIds($entityClassName, $ids);
    }

    /**
     * Sets tags for $entity
     *
     * @param object           $entity
     * @param Collection|Tag[] $tags
     */
    public function setTags($entity, $tags)
    {
        if ($entity instanceof Taggable) {
            $entity->setTags($tags);
        } else {
            $entityClassName = ClassUtils::getClass($entity);
            $entityId        = TaggableHelper::getEntityId($entity);

            $this->storage[$entityClassName][$entityId] = $tags;
        }
    }

    /**
     * Get tags for entity
     *
     * @param object $entity
     *
     * @return Collection|array
     */
    public function getTags($entity)
    {
        if ($entity instanceof Taggable) {
            return $entity->getTags();
        } else {
            $entityClassName = ClassUtils::getClass($entity);
            $entityId        = TaggableHelper::getEntityId($entity);
            if (!isset($this->storage[$entityClassName][$entityId])) {
                $this->storage[$entityClassName][$entityId] = $this->fetchTags(
                    $entity,
                    null
                );
            }

            return $this->storage[$entityClassName][$entityId];
        }
    }

    /**
     * Adds multiple tags on the given entity
     *
     * @param Collection|Tag[] $tags   Array of Tag objects
     * @param object           $entity entity
     */
    public function addTags($tags, $entity)
    {
        foreach ($tags as $tag) {
            $this->addTag($tag, $entity);
        }
    }

    /**
     * Add tag for entity
     *
     * @param Tag    $tag
     * @param object $entity
     */
    public function addTag(Tag $tag, $entity)
    {
        $tags = $this->getTags($entity);
        if (!$tags->contains($tag)) {
            $tags->add($tag);
        }
    }

    /**
     * Remove tagging related to tags by params
     *
     * @param object                 $entity
     * @param User|null              $owner
     * @param Collection|Tag[]|int[] $tags
     *
     * @return int
     */
    public function deleteTagging($entity, $tags, User $owner = null)
    {
        $tagIds     = $this->prepareTagIds($tags);
        $repository = $this->getTagsRepository();

        return $repository->deleteTaggingByParams(
            $tagIds,
            ClassUtils::getClass($entity),
            TaggableHelper::getEntityId($entity),
            $owner
        );
    }

    /**
     * Remove tagging related to tags by params
     *
     * @param Collection|Tag[]|int[] $tagIds
     * @param string                 $entityName
     * @param int                    $recordId
     * @param User                   $createdBy
     *
     * @return int
     *
     * @deprecated Use {@see deleteTagging} instead
     */
    public function deleteTaggingByParams($tagIds, $entityName, $recordId, $createdBy = null)
    {
        /** @var TagRepository $repository */
        $repository = $this->em->getRepository($this->tagClass);
        $tagIds     = $this->prepareTagIds($tagIds);

        return $repository->deleteTaggingByParams($tagIds, $entityName, $recordId, $createdBy);
    }

    /**
     * Loads or creates tag by name
     *
     * @param string            $name         Name of tag
     * @param Organization|null $organization Current organization if not specified
     *
     * @return Tag[]
     */
    public function loadOrCreateTag($name, Organization $organization = null)
    {
        $tags = $this->loadOrCreateTags([$name], $organization);

        return reset($tags);
    }

    /**
     * Loads or creates multiples tags from a list of tag names
     *
     * @param array             $names        Array of tag names
     * @param Organization|null $organization Current organization if not specified
     *
     * @return Tag[]
     */
    public function loadOrCreateTags(array $names, Organization $organization = null)
    {
        if (empty($names)) {
            return [];
        }

        $usedOrganization = $organization ? : $this->getOrganization();

        $names = array_unique(array_map('trim', $names));
        $tags  = $this->em->getRepository($this->tagClass)->findBy(
            ['name' => $names, 'organization' => $usedOrganization]
        );

        $loadedNames = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $loadedNames[] = $tag->getName();
        }

        $missingNames = array_udiff($names, $loadedNames, 'strcasecmp');
        if (sizeof($missingNames)) {
            foreach ($missingNames as $name) {
                $tag = $this->createTag($name);

                $tags[] = $tag;
            }
        }

        return $tags;
    }

    /**
     * Prepare array
     *
     * @param object            $entity
     * @param Collection|null   $tags
     * @param Organization|null $organization Current organization if not specified
     *
     * @return array
     */
    public function getPreparedArray($entity, $tags = null, Organization $organization = null)
    {
        if (null === $tags) {
            $this->loadTagging($entity, $organization);
            $tags = $this->getTags($entity);
        }
        $result = [];

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $entry = [
                'name' => $tag->getName()
            ];
            if (!$tag->getId()) {
                $entry = array_merge(
                    $entry,
                    [
                        'id'    => $tag->getName(),
                        'url'   => false,
                        'owner' => true
                    ]
                );
            } else {
                $entry = array_merge(
                    $entry,
                    [
                        'id'    => $tag->getId(),
                        'url'   => $this->router->generate('oro_tag_search', ['id' => $tag->getId()]),
                        'owner' => false
                    ]
                );
            }

            $taggingCollection = $tag->getTagging()->filter(
                function (Tagging $tagging) use ($entity) {
                    // only use tagging entities that related to current entity
                    return
                        $tagging->getEntityName() === ClassUtils::getClass($entity) &&
                        $tagging->getRecordId() === TaggableHelper::getEntityId($entity);
                }
            );

            /** @var Tagging $tagging */
            foreach ($taggingCollection as $tagging) {
                if ($owner = $tagging->getOwner()) {
                    if ($this->getUser()->getId() == $owner->getId()) {
                        $entry['owner'] = true;
                    }
                }
            }

            $entry['moreOwners'] = $taggingCollection->count() > 1;

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Saves tags for the given taggable entity
     *
     * @param object       $entity       entity
     * @param bool         $flush        Whether to flush the changes (default true)
     * @param Organization $organization Current one if not specified
     */
    public function saveTagging($entity, $flush = true, Organization $organization = null)
    {
        $owner = $this->getUser();

        // Tag[]  - assigned to the entity.
        $oldTags = $this->fetchTags($entity, $owner, false, $organization);

        // Modified entity Tags, could contains new tags, or does not contains tags that need to remove.
        $newTags = $this->getTags($entity);

        // Taggable submitted form entity contains array of [autocomplete = [], all => Tag[], owner => Tag[]] tags.
        if (isset($newTags['all'], $newTags['owner'])) {
            $newAllTags   = new ArrayCollection($newTags['all']);
            $newOwnerTags = new ArrayCollection($newTags['owner']);
        } else {
            $newAllTags   = $newTags;
            $newOwnerTags = $newTags->filter(
                function (Tag $tag) use ($owner) {
                    return
                        $tag->getOwner() === $owner ||
                        $tag->getId() === null;
                }
            );
        }

        $tagsToAdd    = $newOwnerTags->filter(
            function ($tag) use ($oldTags) {
                return !$oldTags->exists($this->getComparePredicate($tag));
            }
        );
        $tagsToDelete = $oldTags->filter(
            function ($tag) use ($newOwnerTags) {
                return !$newOwnerTags->exists($this->getComparePredicate($tag));
            }
        );

        if (!$tagsToDelete->isEmpty() && $this->securityFacade->isGranted(self::ACL_RESOURCE_ASSIGN_ID_KEY)) {
            $this->deleteTagging($entity, $tagsToDelete, $owner);
        }

        // process if current user allowed to remove other's tag links
        if ($owner && $this->securityFacade->isGranted(self::ACL_RESOURCE_REMOVE_ID_KEY)) {
            // get 'not mine' taggings
            $oldTags      = $this->fetchTags($entity, $owner, true, $organization);
            $tagsToDelete = $oldTags->filter(
                function ($tag) use ($newAllTags) {
                    return !$newAllTags->exists($this->getComparePredicate($tag));
                }
            );
            if (!$tagsToDelete->isEmpty()) {
                $this->deleteTagging($entity, $tagsToDelete);
            }
        }

        if (!$tagsToAdd->isEmpty()) {
            $this->persistTags($entity, $tagsToAdd);
            if ($flush) {
                $this->em->flush();
            }
        }
    }

    /**
     * Loads all tags for the given entity
     *
     * @param object       $entity       entity
     * @param Organization $organization Organization to load tags for or current one if not specified
     *
     * @return $this
     * @throws \Exception
     */
    public function loadTagging($entity, Organization $organization = null)
    {
        $tags = $this->fetchTags($entity, null, false, $organization);
        $this->setTags($entity, $tags);

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return callable
     *
     * @@deprecated
     */
    public function compareCallback($tag)
    {
        return $this->getComparePredicate($tag);
    }

    /**
     * @param Tag $tag
     *
     * @return \Closure
     */
    protected function getComparePredicate(Tag $tag)
    {
        return function ($index, $item) use ($tag) {
            /** @var Tag $item */
            return $item->getName() === $tag->getName();
        };
    }

    /**
     * @param object           $entity
     * @param Collection|Tag[] $tags
     */
    protected function persistTags($entity, $tags)
    {
        foreach ($tags as $tag) {
            if ($this->getUser() &&
                (!$this->securityFacade->isGranted(self::ACL_RESOURCE_ASSIGN_ID_KEY) ||
                    (!$this->securityFacade->isGranted(self::ACL_RESOURCE_CREATE_ID_KEY) && !$tag->getId())
                )
            ) {
                // skip tags that have not ID because user not granted to create tags
                continue;
            }

            $tagging = $this->createTagging($tag, $entity);

            $this->em->persist($tag);
            $this->em->persist($tagging);
        }
    }

    /**
     * Creates a new Tag object
     *
     * @param  string $name Tag name
     *
     * @return Tag
     */
    protected function createTag($name)
    {
        return new $this->tagClass($name);
    }

    /**
     * Creates a new Tagging object
     *
     * @param  Tag    $tag    Tag object
     * @param  object $entity entity
     *
     * @return Tagging
     */
    protected function createTagging(Tag $tag, $entity)
    {
        return new $this->taggingClass($tag, $entity);
    }

    /**
     * Fetch tags for the given entity
     *
     * @param object       $entity entity
     * @param User         $owner
     * @param bool         $all
     * @param Organization $organization
     *
     * @return Collection
     */
    protected function fetchTags($entity, $owner, $all = false, Organization $organization = null)
    {
        $repository       = $this->getTagsRepository();
        $usedOrganization = $organization ? : $this->getOrganization();

        $elements = $repository->getTags(
            ClassUtils::getClass($entity),
            TaggableHelper::getEntityId($entity),
            $owner,
            $all,
            $usedOrganization
        );

        return new ArrayCollection($elements);
    }

    /**
     * Return current user
     *
     * @return User
     */
    protected function getUser()
    {
        return $this->securityFacade->getLoggedUser();
    }

    /**
     * Return current organization
     *
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->securityFacade->getOrganization();
    }

    /**
     * @param Collection|int[] $tagIds
     *
     * @return int[]
     */
    protected function prepareTagIds($tagIds)
    {
        if ($tagIds instanceof Collection) {
            return array_map(
                function (Tag $item) {
                    return $item->getId();
                },
                $tagIds->toArray()
            );

        } elseif (!is_array($tagIds)) {
            return [];
        }

        return $tagIds;
    }

    /**
     * @return TagRepository
     */
    protected function getTagsRepository()
    {
        $repository = $this->em->getRepository($this->tagClass);

        return $repository;
    }
}
