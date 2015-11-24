<?php

namespace Oro\Bundle\TagBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\UserBundle\Entity\User;

class TagRepository extends EntityRepository
{
    /**
     * Returns tags related to entity
     *
     * If $owner is null return all tags for entity.
     * If $owner is not null and $all is true return all tags excluded $owner.
     * If $owner is not null and $all is false return all $owner tags.
     *
     * @param string            $entityName
     * @param int               $entityId
     * @param User|null         $owner
     * @param bool|false        $all
     * @param Organization|null $organization
     *
     * @return array
     */
    public function getTags(
        $entityName,
        $entityId,
        User $owner = null,
        $all = false,
        Organization $organization = null
    ) {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->innerJoin('t.tagging', 't2', Join::WITH, 't2.recordId = :recordId AND t2.entityName = :entityName')
            ->setParameter('recordId', $entityId)
            ->setParameter('entityName', $entityName);

        if (null !== $owner) {
            $qb->where('t2.owner ' . ($all ? '!=' : '=') . ' :owner')
                ->setParameter('owner', $owner);
        }

        if (null !== $organization) {
            $qb->andWhere('t.organization = :organization')
                ->setParameter('organization', $organization);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Remove tags related to entity
     *
     * @param Tag[]|int[] $tags
     * @param string      $entityName
     * @param int         $recordId
     * @param User|null   $owner
     *
     * @return int
     */
    public function deleteTaggingByParams(array $tags, $entityName, $recordId, User $owner = null)
    {
        $builder = $this->_em->createQueryBuilder();
        $builder
            ->delete('OroTagBundle:Tagging', 't')
            ->where('t.entityName = :entityName')
            ->andWhere('t.recordId = :recordId')
            ->setParameter('entityName', $entityName)
            ->setParameter('recordId', $recordId);

        if (!empty($tags)) {
            $builder->andWhere($builder->expr()->in('t.tag', $tags));
        }

        if (null !== $owner) {
            $builder->andWhere('t.owner = :owner')
                ->setParameter('owner', $owner);
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * Returns tags with taggings loaded by resource
     *
     * @param object       $resource
     * @param int|null     $createdBy
     * @param bool         $all
     * @param Organization $organization
     *
     * @return array
     *
     * @deprecated Use {@see getTags} instead
     */
    public function getTagging($resource, $createdBy = null, $all = false, Organization $organization = null)
    {
        $recordId = TagManager::getEntityId($resource);

        return $this->getTags(ClassUtils::getClass($resource), $recordId, $createdBy, $all, $organization);
    }
}
