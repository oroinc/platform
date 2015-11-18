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
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isTaggable($entity)
    {
        return
            $entity instanceof Taggable ||
            $this->tagConfigProvider->getConfig($entity)->is('enabled');
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
        if (is_null($tags)) {
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
                    $taggableId = $entity instanceof Taggable ? $entity->getTaggableId() : $entity->getId();

                    // only use tagging entities that related to current entity
                    return
                        $tagging->getEntityName() === ClassUtils::getClass($entity) &&
                        $tagging->getRecordId() === $taggableId;
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
     * Saves tags for the given taggable entity
     *
     * @param object       $entity       entity
     * @param bool         $flush        Whether to flush the changes (default true)
     * @param Organization $organization Current one if not specified
     */
    public function saveTagging($entity, $flush = true, Organization $organization = null)
    {
        $createdBy = $this->getUser();

        // Tag[]  - assigned to the entity.
        $oldTags   = $this->fetchTags($entity, $createdBy, false, $organization);

        // @todo: Should be refactored in CRM-4598.
        // Need to specify tags for update, cause when form submitted, taggable entity contains
        // information about [autocomplete = [], all => Tag[], owner => Tag[]] tags.
        $newTags   = $this->getTags($entity);

        if (isset($newTags['all'], $newTags['owner'])) {
            $newOwnerTags = new ArrayCollection($newTags['owner']);
            $newAllTags   = new ArrayCollection($newTags['all']);

            $tagsToAdd    = $newOwnerTags->filter(
                function ($tag) use ($oldTags) {
                    return !$oldTags->exists($this->compareCallback($tag));
                }
            );
            $tagsToDelete = $oldTags->filter(
                function ($tag) use ($newOwnerTags) {
                    return !$newOwnerTags->exists($this->compareCallback($tag));
                }
            );

            if (!$tagsToDelete->isEmpty() && $this->securityFacade->isGranted(self::ACL_RESOURCE_ASSIGN_ID_KEY)) {
                $this->deleteTaggingByParams(
                    $tagsToDelete,
                    ClassUtils::getClass($entity),
                    $entity->getTaggableId(),
                    $this->getUser()
                );
            }

            // process if current user allowed to remove other's tag links
            if (!$this->getUser() || $this->securityFacade->isGranted(self::ACL_RESOURCE_REMOVE_ID_KEY)) {
                // get 'not mine' taggings
                $oldTags      = $this->fetchTags($entity, $createdBy, true, $organization);
                $tagsToDelete = $oldTags->filter(
                    function ($tag) use ($newAllTags) {
                        return !$newAllTags->exists($this->compareCallback($tag));
                    }
                );
                if (!$tagsToDelete->isEmpty()) {
                    $this->deleteTaggingByParams(
                        $tagsToDelete,
                        ClassUtils::getClass($entity),
                        $entity->getTaggableId()
                    );
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
     * @param Tag $tag
     *
     * @return callable
     */
    public function compareCallback($tag)
    {
        return function ($index, $item) use ($tag) {
            /** @var Tag $item */
            return $item->getName() == $tag->getName();
        };
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
            throw new \Exception('Entity should be taggable');
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
     * @return array
     */
    public function deleteTaggingByParams($tagIds, $entityName, $recordId, $createdBy = null)
    {
        if (!$tagIds) {
            $tagIds = [];
        } elseif ($tagIds instanceof Collection) {
            $tagIds = array_map(
                function (Tag $item) {
                    return $item->getId();
                },
                $tagIds->toArray()
            );
        }

        /** @var TagRepository $repository */
        $repository = $this->em->getRepository($this->tagClass);

        return $repository->deleteTaggingByParams($tagIds, $entityName, $recordId, $createdBy);
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
     * @param User         $createdBy
     * @param bool         $all
     * @param Organization $organization
     *
     * @return Collection
     */
    protected function fetchTags($entity, $createdBy, $all = false, Organization $organization = null)
    {
        /** @var TagRepository $repository */
        $repository       = $this->em->getRepository($this->tagClass);
        $usedOrganization = $organization ?: $this->getOrganization();

        return new ArrayCollection($repository->getTagging($entity, $createdBy, $all, $usedOrganization));
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
}
