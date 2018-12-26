<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TagBundle\Entity\Repository\TagRepository;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides methods to get/set/add tags for entities
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TagManager
{
    const ACL_RESOURCE_CREATE_ID_KEY = 'oro_tag_create';
    const ACL_RESOURCE_ASSIGN_ID_KEY = 'oro_tag_assign_unassign';

    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $tagClass;

    /** @var string */
    protected $taggingClass;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var Router */
    protected $router;

    /** @var array */
    protected $storage = [];

    /**
     * @param EntityManager                 $em
     * @param string                        $tagClass     - FQCN
     * @param string                        $taggingClass - FQCN
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param Router                        $router
     */
    public function __construct(
        EntityManager $em,
        $tagClass,
        $taggingClass,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        Router $router
    ) {
        $this->em = $em;
        $this->tagClass = $tagClass;
        $this->taggingClass = $taggingClass;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->router = $router;
    }

    /**
     * @param @param string $entityClassName The FQCN of the entity
     * @param array $ids
     *
     * @return array [id, name, entityId, owner]
     */
    public function getTagsByEntityIds($entityClassName, array $ids)
    {
        $user = $this->getUser();
        if (!$user) {
            return [];
        }
        $repository = $this->getTagsRepository();

        return $repository->getTagsByEntityIds($entityClassName, $ids, $user);
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
        }

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

        $usedOrganization = $organization ?: $this->getOrganization();

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
                'name' => $tag->getName(),
                'ownerFirstName' => null,
                'ownerLastName' => null
            ];
            if ($tag->getId()) {
                $entry = array_merge(
                    $entry,
                    [
                        'id'    => $tag->getId(),
                        'url'   => $this->router->generate('oro_tag_search', ['id' => $tag->getId()]),
                        'owner' => false
                    ]
                );
            } else {
                $entry = array_merge(
                    $entry,
                    [
                        'id'    => $tag->getName(),
                        'url'   => false,
                        'owner' => true
                    ]
                );
            }

            $criteria = Criteria::create()->where(Criteria::expr()->andX(
                Criteria::expr()->eq('entityName', ClassUtils::getClass($entity)),
                Criteria::expr()->eq('recordId', TaggableHelper::getEntityId($entity))
            ));

            $taggingCollection = $tag->getTagging()->matching($criteria);

            /** @var Tagging $tagging */
            foreach ($taggingCollection as $tagging) {
                if ($owner = $tagging->getOwner()) {
                    if ($this->getUser()->getId() == $owner->getId()) {
                        $entry['owner'] = true;
                    }
                    $entry['ownerFirstName'] = $owner->getFirstName();
                    $entry['ownerLastName'] = $owner->getLastName();
                }
            }

            $entry['moreOwners'] = $taggingCollection->count() > 1;
            $entry['backgroundColor'] = $tag->getBackgroundColor();

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
        // Tags stored for the entity.
        $oldAllTags = $this->fetchTags($entity, null, false, $organization);

        // Actual tags for current entity, could contains new tags, or does not contains tags that need to remove.
        $tags = $this->getTags($entity);

        // Get all tags that should be added
        $tagsToAdd = $tags->filter(
            function ($tag) use ($oldAllTags) {
                return !$oldAllTags->exists($this->getComparePredicate($tag));
            }
        );
        if (!$tagsToAdd->isEmpty()) {
            $this->persistTags($entity, $tagsToAdd);
        }

        // Get all tags that should be deleted
        $tagsToDelete = $oldAllTags->filter(
            function (Tag $tag) use ($tags) {
                return !$tags->exists($this->getComparePredicate($tag));
            }
        );
        if (!$tagsToDelete->isEmpty()) {
            $this->persistDeleteTags($entity, $tagsToDelete);
        }

        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * @param object     $entity
     * @param Collection $tags
     */
    protected function persistDeleteTags($entity, Collection $tags)
    {
        if (!$tags->isEmpty()) {
            if (!$this->authorizationChecker->isGranted(self::ACL_RESOURCE_ASSIGN_ID_KEY)) {
                throw new AccessDeniedException("User does not have access to assign/unassign tags.");
            }
            $this->deleteTagging($entity, $tags);
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
     * @deprecated Use {@see getComparePredicate} instead
     */
    public function compareCallback($tag)
    {
        return $this->getComparePredicate($tag);
    }

    /**
     * Deletes tags relations for given entity class.
     *
     * @param string $entityClassName
     *
     * @return int
     */
    public function deleteRelations($entityClassName)
    {
        return $this->getTagsRepository()->deleteRelations($entityClassName);
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
        if (!$this->authorizationChecker->isGranted(self::ACL_RESOURCE_ASSIGN_ID_KEY)) {
            throw new AccessDeniedException("User does not have access to assign/unassign tags.");
        }

        foreach ($tags as $tag) {
            if (!$this->authorizationChecker->isGranted(self::ACL_RESOURCE_CREATE_ID_KEY) && !$tag->getId()) {
                throw new AccessDeniedException("User does not have access to create tags.");
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
        $usedOrganization = $organization ?: $this->getOrganization();

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
     * @return null|OrganizationInterface
     */
    protected function getOrganizationByUser()
    {
        $user = $this->getUser();

        return $user ? $user->getOrganization() : null;
    }

    /**
     * Return current user
     *
     * @return User
     */
    protected function getUser()
    {
        return $this->tokenAccessor->getUser();
    }

    /**
     * Return current organization
     *
     * @return OrganizationInterface
     */
    protected function getOrganization()
    {
        return $this->tokenAccessor->getOrganization()
            ? $this->tokenAccessor->getOrganization()
            : $this->getOrganizationByUser();
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
        }

        if (is_array($tagIds)) {
            return $tagIds;
        }

        return [];
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
