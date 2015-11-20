<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\TagBundle\Entity\Repository\TagRepository;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

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

    /** @var ObjectMapper */
    protected $mapper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var Router */
    protected $router;

    /** @var ConfigProvider */
    protected $tagConfigProvider;

    protected static $storage = [];

    /**
     * @param EntityManager  $em
     * @param string         $tagClass     - FQCN
     * @param string         $taggingClass - FQCN
     * @param ObjectMapper   $mapper
     * @param SecurityFacade $securityFacade
     * @param Router         $router
     * @param ConfigProvider $tagConfigProvider
     */
    public function __construct(
        EntityManager $em,
        $tagClass,
        $taggingClass,
        ObjectMapper $mapper,
        SecurityFacade $securityFacade,
        Router $router,
        ConfigProvider $tagConfigProvider
    ) {
        $this->em                = $em;
        $this->tagClass          = $tagClass;
        $this->taggingClass      = $taggingClass;
        $this->mapper            = $mapper;
        $this->securityFacade    = $securityFacade;
        $this->router            = $router;
        $this->tagConfigProvider = $tagConfigProvider;
    }

    /**
     * Checks if entity taggable
     * Entity is taggable if it inherit Taggable interface or it configured as taggable.
     *
     * @param string|object $className
     *
     * @return bool
     */
    public function isTaggable($className)
    {
        return
            $this->tagConfigProvider->getConfig($className)->is('enabled') ||
            $this->isImplementsTaggable($className);
    }

    /**
     * Checks if entity immutable
     * For entities that inherit Taggable interface tags are always enabled.
     *
     * @param object|string $className
     *
     * @return bool
     */
    public function isImmutable($className)
    {
        return
            $this->tagConfigProvider->getConfig($className)->is('immutable') ||
            $this->isImplementsTaggable($className);
    }

    /**
     * Checks if entity class inherit Taggable interface
     *
     * @param object|string $className
     *
     * @return bool
     */
    public function isImplementsTaggable($className)
    {
        return is_a($className, 'Oro\Bundle\TagBundle\Entity\Taggable', true);
    }

    /**
     * @param object $entity
     *
     * @return int
     * @todo: Should be implemented another approach for accessing entity identifier?
     */
    public static function getEntityId($entity)
    {
        return $entity instanceof Taggable ? $entity->getTaggableId() : $entity->getId();
    }

    /**
     * @param object    $entity
     * @param User|null $owner
     * @param int[]     $tagIds
     *
     * @return int
     */
    public function deleteEntityTags($entity, array $tagIds = [], User $owner = null)
    {
        /** @var TagRepository $repository */
        $repository = $this->em->getRepository($this->tagClass);

        return $repository->deleteEntityTags(
            $tagIds,
            ClassUtils::getClass($entity),
            self::getEntityId($entity),
            $owner
        );
    }

    /**
     * @param object $entity
     * @param mixed  $tags
     */
    public function setEntityTags($entity, $tags)
    {
        if ($entity instanceof Taggable) {
            $entity->setTags($tags);
        } elseif ($this->tagConfigProvider->getConfig($entity)->is('enabled')) {
            // @todo: Storage should be refactored in CRM-4598.
            self::$storage[ClassUtils::getRealClass($entity)][$entity->getId()] = $tags;
        }
    }

    /**
     * Adds multiple tags on the given entity
     *
     * @param Collection|Tag[] $tags   Array of Tag objects
     * @param object           $entity entity
     *
     * @throws \Exception
     */
    public function addTags($tags, $entity)
    {
        foreach ($tags as $tag) {
            $this->addTag($tag, $entity);
        }
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
                        $tagging->getRecordId() === $this->getEntityId($entity);
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
        $oldTags   = $this->fetchTags($entity, $owner, false, $organization);

        // @todo: Should be refactored in CRM-4598.
        // Need to specify tags for update, cause when form submitted, taggable entity contains
        // information about [autocomplete = [], all => Tag[], owner => Tag[]] tags.
        $newTags   = $this->getTags($entity);

        if (isset($newTags['all'], $newTags['owner'])) {
            $newOwnerTags = new ArrayCollection($newTags['owner']);
            $newAllTags   = new ArrayCollection($newTags['all']);

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
                $this->deleteEntityTags($entity, $this->prepareTagIds($tagsToDelete), $owner);
            }

            // process if current user allowed to remove other's tag links
            if (!$owner || $this->securityFacade->isGranted(self::ACL_RESOURCE_REMOVE_ID_KEY)) {
                // get 'not mine' taggings
                $oldTags      = $this->fetchTags($entity, $owner, true, $organization);
                $tagsToDelete = $oldTags->filter(
                    function ($tag) use ($newAllTags) {
                        return !$newAllTags->exists($this->getComparePredicate($tag));
                    }
                );
                if (!$tagsToDelete->isEmpty()) {
                    $this->deleteEntityTags($entity, $this->prepareTagIds($tagsToDelete));
                }
            }

            if (!$tagsToAdd->isEmpty()) {
                $this->persistTags($entity, $tagsToAdd);
                if ($flush) {
                    $this->em->flush();
                }
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
        if (!$this->isTaggable($entity)) {
            throw new \InvalidArgumentException('Entity should be taggable');
        }

        $tags = $this->fetchTags($entity, null, false, $organization);
        $this->addTags($tags, $entity);

        return $this;
    }

    /**
     * Remove tagging related to tags by params
     *
     * @param Collection|int[] $tagIds
     * @param string           $entityName
     * @param int              $recordId
     * @param User             $createdBy
     *
     * @return int
     *
     * @deprecated Use {@see deleteEntityTags} instead
     */
    public function deleteTaggingByParams($tagIds, $entityName, $recordId, $createdBy = null)
    {
        $tagIds = $this->prepareTagIds($tagIds);
        /** @var TagRepository $repository */
        $repository = $this->em->getRepository($this->tagClass);

        return $repository->deleteEntityTags($tagIds, $entityName, $recordId, $createdBy);
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
     * Add tag for entity
     *
     * @param Tag    $tag
     * @param object $entity
     */
    protected function addTag(Tag $tag, $entity)
    {
        $tags = $this->getTags($entity);
        if (!$tags->contains($tag)) {
            $tags->add($tag);
        }
    }

    /**
     * returns entity tags
     *
     * @param object $entity
     *
     * @return array|Collection
     */
    public function getEntityTags($entity)
    {
        return $this->getTags($entity);
    }

    /**
     * Get tags for entity
     *
     * @param object $entity
     *
     * @return Collection|array
     */
    protected function getTags($entity)
    {
        if ($entity instanceof Taggable) {
            return $entity->getTags();
        } elseif ($this->tagConfigProvider->getConfig($entity)->is('enabled')) {
            // @todo: Storage should be refactored in CRM-4598.
            if (!isset(self::$storage[ClassUtils::getRealClass($entity)][$entity->getId()])) {
                self::$storage[ClassUtils::getRealClass($entity)][$entity->getId()] = new ArrayCollection();
            }

            return self::$storage[ClassUtils::getRealClass($entity)][$entity->getId()];
        }
    }

    /**
     * @param object     $entity
     * @param Collection $tags
     */
    protected function persistTags($entity, Collection $tags)
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

            $alias   = $this->mapper->getEntityConfig(ClassUtils::getClass($entity));
            $tagging = $this->createTagging($tag, $entity)->setAlias($alias['alias']);

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
        /** @var TagRepository $repository */
        $repository       = $this->em->getRepository($this->tagClass);
        $usedOrganization = $organization ?: $this->getOrganization();

        $elements = $repository->getEntityTags(
            ClassUtils::getClass($entity),
            $entity->getId(),
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
     * @param $tagIds
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
}
