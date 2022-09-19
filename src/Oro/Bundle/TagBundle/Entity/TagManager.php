<?php

namespace Oro\Bundle\TagBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TagBundle\Entity\Repository\TagRepository;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides methods to manage tags for entities.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TagManager
{
    private const ACL_RESOURCE_CREATE = 'oro_tag_create';
    private const ACL_RESOURCE_ASSIGN = 'oro_tag_assign_unassign';

    private ManagerRegistry $doctrine;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private UrlGeneratorInterface $urlGenerator;
    /** @var array [entity class => [entity id => tags (Collection object), ...], ...] */
    private array $storage = [];

    public function __construct(
        ManagerRegistry $doctrine,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->doctrine = $doctrine;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Gets tags by the given entity IDs.
     *
     * @param string $entityClass
     * @param array  $ids
     *
     * @return array [id, name, entityId, owner]
     */
    public function getTagsByEntityIds(string $entityClass, array $ids): array
    {
        $user = $this->getUser();
        if (!$user) {
            return [];
        }

        return $this->getRepository()->getTagsByEntityIds($entityClass, $ids, $user);
    }

    /**
     * Sets tags for the given entity.
     *
     * @param object           $entity
     * @param Collection|Tag[] $tags
     */
    public function setTags(object $entity, Collection|array $tags): void
    {
        if ($entity instanceof Taggable) {
            $entity->setTags($tags);
        } else {
            $entityClass = ClassUtils::getClass($entity);
            $entityId = TaggableHelper::getEntityId($entity);
            $this->storage[$entityClass][$entityId] = \is_array($tags) ? new ArrayCollection($tags) : $tags;
        }
    }

    /**
     * Gets tags for the given entity.
     */
    public function getTags(object $entity): Collection
    {
        if ($entity instanceof Taggable) {
            return $entity->getTags();
        }

        $entityClass = ClassUtils::getClass($entity);
        $entityId = TaggableHelper::getEntityId($entity);
        if (!isset($this->storage[$entityClass][$entityId])) {
            $this->storage[$entityClass][$entityId] = $this->fetchTags($entity);
        }

        return $this->storage[$entityClass][$entityId];
    }

    /**
     * Adds multiple tags for the given entity.
     *
     * @param Collection|Tag[] $tags
     * @param object           $entity
     */
    public function addTags(Collection|array $tags, object $entity): void
    {
        foreach ($tags as $tag) {
            $this->addTag($tag, $entity);
        }
    }

    /**
     * Adds a tag for the given entity.
     */
    public function addTag(Tag $tag, object $entity): void
    {
        $tags = $this->getTags($entity);
        if (!$tags->contains($tag)) {
            $tags->add($tag);
        }
    }

    /**
     * Deletes multiple tags for the given entity.
     *
     * @param Collection|Tag[] $tags
     * @param object           $entity
     */
    public function deleteTags(Collection|array $tags, object $entity): void
    {
        foreach ($tags as $tag) {
            $this->deleteTag($tag, $entity);
        }
    }

    /**
     * Deletes a tag for the given entity.
     */
    public function deleteTag(Tag $tag, object $entity): void
    {
        $tags = $this->getTags($entity);
        if ($tags->contains($tag)) {
            $tags->removeElement($tag);
        }
    }

    /**
     * Loads or creates a tag by name.
     */
    public function loadOrCreateTag(string $name, Organization $organization = null): Tag
    {
        $tags = $this->loadOrCreateTags([$name], $organization);

        return reset($tags);
    }

    /**
     * Loads or creates multiples tags by names.
     *
     * @param string[]          $names The names of tags
     * @param Organization|null $organization
     *
     * @return Tag[]
     */
    public function loadOrCreateTags(array $names, Organization $organization = null): array
    {
        if (empty($names)) {
            return [];
        }

        $names = array_unique(array_map('trim', $names));
        $tags = $this->getRepository()->findBy([
            'name'         => $names,
            'organization' => $organization ?? $this->getOrganization()
        ]);

        $loadedNames = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $loadedNames[] = $tag->getName();
        }

        $missingNames = array_udiff($names, $loadedNames, 'strcasecmp');
        if (sizeof($missingNames)) {
            foreach ($missingNames as $name) {
                $tags[] = new Tag($name);
            }
        }

        return $tags;
    }

    /**
     * Gets entities that are marked by the given tag.
     *
     * @param Tag $tag
     *
     * @return object[]
     */
    public function getEntities(Tag $tag): array
    {
        return $this->getRepository()->getEntities($tag);
    }

    public function getPreparedArray(object $entity, Collection $tags = null, Organization $organization = null): array
    {
        if (null === $tags) {
            $this->loadTagging($entity, $organization);
            $tags = $this->getTags($entity);
        }

        $result = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $entry = [
                'name'           => $tag->getName(),
                'ownerFirstName' => null,
                'ownerLastName'  => null
            ];
            if ($tag->getId()) {
                $entry = array_merge(
                    $entry,
                    [
                        'id'    => $tag->getId(),
                        'url'   => $this->urlGenerator->generate('oro_tag_search', ['id' => $tag->getId()]),
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
                $owner = $tagging->getOwner();
                if ($owner) {
                    if ($this->getUser()->getId() === $owner->getId()) {
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
     * Saves tags for the given entity.
     */
    public function saveTagging(object $entity, bool $flush = true, Organization $organization = null): void
    {
        // Tags stored for the entity.
        $oldAllTags = $this->fetchTags($entity, false, $organization);

        // Actual tags for current entity, could contains new tags, or does not contains tags that need to remove.
        $tags = $this->getTags($entity);

        // Get all tags that should be added
        $tagsToAdd = $tags->filter(function ($tag) use ($oldAllTags) {
            return !$oldAllTags->exists($this->getComparePredicate($tag));
        });
        if (!$tagsToAdd->isEmpty()) {
            $this->persistTags($entity, $tagsToAdd);
        }

        // Get all tags that should be deleted
        $tagsToDelete = $oldAllTags->filter(function (Tag $tag) use ($tags) {
            return !$tags->exists($this->getComparePredicate($tag));
        });
        if (!$tagsToDelete->isEmpty()) {
            $this->persistDeleteTags($entity, $tagsToDelete);
        }

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Loads all tags for the given entity.
     */
    public function loadTagging(object $entity, Organization $organization = null): void
    {
        $tags = $this->fetchTags($entity, false, $organization);
        $this->setTags($entity, $tags);
    }

    /**
     * Deletes all tags for the given entity.
     *
     * @param object                 $entity
     * @param User|null              $owner
     * @param Collection|Tag[]|int[] $tags
     *
     * @return int
     */
    public function deleteTagging(object $entity, Collection|array $tags, User $owner = null): int
    {
        $entityClass = ClassUtils::getClass($entity);
        $entityId = TaggableHelper::getEntityId($entity);

        unset($this->storage[$entityClass][$entityId]);

        if ($tags instanceof Collection) {
            $tags = $tags->toArray();
        }

        return $this->getRepository()->deleteTaggingByParams($tags, $entityClass, $entityId, $owner);
    }

    /**
     * Deletes all tags for all entities of the given type.
     */
    public function deleteAllTagging(string $entityClass): int
    {
        return $this->getRepository()->deleteRelations($entityClass);
    }

    private function persistDeleteTags(object $entity, Collection $tags): void
    {
        if (!$tags->isEmpty()) {
            if (!$this->authorizationChecker->isGranted(self::ACL_RESOURCE_ASSIGN)) {
                throw new AccessDeniedException('User does not have access to assign/unassign tags.');
            }
            $this->deleteTagging($entity, $tags);
        }
    }

    private function getComparePredicate(Tag $tag): \Closure
    {
        return function ($index, Tag $item) use ($tag) {
            return $item->getName() === $tag->getName();
        };
    }

    private function persistTags(object $entity, Collection $tags): void
    {
        if (!$this->authorizationChecker->isGranted(self::ACL_RESOURCE_ASSIGN)) {
            throw new AccessDeniedException('User does not have access to assign/unassign tags.');
        }

        $em = $this->getEntityManager();
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            if (!$this->authorizationChecker->isGranted(self::ACL_RESOURCE_CREATE) && !$tag->getId()) {
                throw new AccessDeniedException('User does not have access to create tags.');
            }

            $em->persist($tag);
            $em->persist(new Tagging($tag, $entity));
        }
    }

    private function fetchTags(object $entity, bool $all = false, Organization $organization = null): Collection
    {
        $elements = $this->getRepository()->getTags(
            ClassUtils::getClass($entity),
            TaggableHelper::getEntityId($entity),
            null,
            $all,
            $organization ?: $this->getOrganization()
        );

        return new ArrayCollection($elements);
    }

    private function getUser(): ?User
    {
        return $this->tokenAccessor->getUser();
    }

    private function getOrganization(): ?Organization
    {
        return $this->tokenAccessor->getOrganization() ?? $this->getUser()?->getOrganization();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(Tag::class);
    }

    private function getRepository(): TagRepository
    {
        return $this->doctrine->getRepository(Tag::class);
    }
}
