<?php

namespace Oro\Bundle\TagBundle\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Taggable;

class TagRepository extends EntityRepository
{
    /**
     * Returns tags with taggings loaded by resource
     *
     * @param Taggable     $resource
     * @param null         $createdBy
     * @param bool         $all
     * @param Organization $organization
     * @return array
     */
    public function getTagging(Taggable $resource, $createdBy = null, $all = false, Organization $organization = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->innerJoin('t.tagging', 't2', Join::WITH, 't2.recordId = :recordId AND t2.entityName = :entityName')
            ->setParameter('recordId', $resource->getTaggableId())
            ->setParameter('entityName', ClassUtils::getClass($resource));

        if (!is_null($createdBy)) {
            $qb->where('t2.owner ' . ($all ? '!=' : '=') . ' :createdBy')
                ->setParameter('createdBy', $createdBy);
        }

        if (!is_null($organization)) {
            $qb->andWhere('t.organization = :organization')
                ->setParameter('organization', $organization);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Remove tagging related to tags by params
     *
     * @param array|int $tagIds
     * @param string $entityName
     * @param int $recordId
     * @param null|int $createdBy
     * @return array
     */
    public function deleteTaggingByParams($tagIds, $entityName, $recordId, $createdBy = null)
    {
        $builder = $this->_em->createQueryBuilder();
        $builder
            ->delete('OroTagBundle:Tagging', 't')
            ->where('t.entityName = :entityName')
            ->setParameter('entityName', $entityName)
            ->andWhere('t.recordId = :recordId')
            ->setParameter('recordId', $recordId);

        if (!empty($tagIds)) {
            $builder->andWhere($builder->expr()->in('t.tag', $tagIds));
        }

        if (!is_null($createdBy)) {
            $builder->andWhere('t.owner = :createdBy')
                ->setParameter('createdBy', $createdBy);
        }

        return $builder->getQuery()->getResult();
    }
}
