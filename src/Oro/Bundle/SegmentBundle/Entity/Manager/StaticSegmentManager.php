<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

class StaticSegmentManager
{
    /** @var EntityManager */
    protected $em;

    /** @var DynamicSegmentQueryBuilder */
    protected $dynamicSegmentQB;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $ownershipMetadataProvider;

    /**
     * @param EntityManager              $em
     * @param DynamicSegmentQueryBuilder $dynamicSegmentQB
     * @param OwnershipMetadataProvider  $ownershipMetadataProvider
     */
    public function __construct(
        EntityManager $em,
        DynamicSegmentQueryBuilder $dynamicSegmentQB,
        OwnershipMetadataProvider $ownershipMetadataProvider
    ) {
        $this->em                        = $em;
        $this->dynamicSegmentQB          = $dynamicSegmentQB;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * Runs static repository restriction query and stores it state into snapshot entity
     * Doctrine does not supports insert in DQL. To increase the speed of query here uses plain sql query.
     *
     * @param Segment $segment
     *
     * @throws \LogicException
     * @throws \Exception
     */
    public function run(Segment $segment)
    {
        if ($segment->getType()->getName() !== SegmentType::TYPE_STATIC) {
            throw new \LogicException('Only static segments could have snapshots.');
        }
        $entityMetadata = $this->em->getClassMetadata($segment->getEntity());

        if (count($entityMetadata->getIdentifierFieldNames()) > 1) {
            throw new \LogicException('Only entities with single identifier supports.');
        }

        $this->em->getRepository('OroSegmentBundle:SegmentSnapshot')->removeBySegment($segment);

        $qb = $this->dynamicSegmentQB->getQueryBuilder($segment);
        $this->applyOrganizationLimit($segment, $qb);
        $qb->setMaxResults($segment->getRecordsLimit());
        $result = $qb->getQuery()->getArrayResult();
        $identifier = $entityMetadata->getSingleIdentifierFieldName();
        $identifierType = $entityMetadata->getTypeOfField($identifier);

        $ids = array_column($result, $identifier);

        foreach ($ids as $id) {
            $segmentSnapshot = new SegmentSnapshot($segment);
            if ($identifierType === 'integer') {
                $segmentSnapshot->setIntegerEntityId($id);
            } else {
                $segmentSnapshot->setEntityId($id);
            }
            $this->em->persist($segmentSnapshot);
        }
        $segment = $this->em->merge($segment);
        $segment->setLastRun(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->em->persist($segment);
        $this->em->flush();
    }

    /**
     * Limit segment data by segment's organization
     *
     * @param Segment      $segment
     * @param QueryBuilder $qb
     */
    protected function applyOrganizationLimit(Segment $segment, QueryBuilder $qb)
    {
        $organizationField = $this->ownershipMetadataProvider
            ->getMetadata($segment->getEntity())
            ->getGlobalOwnerFieldName();
        if ($organizationField) {
            $qb->andWhere(
                sprintf(
                    '%s.%s = %s',
                    $qb->getRootAliases()[0],
                    $organizationField,
                    $segment->getOrganization()->getId()
                )
            );
        }
    }

    /**
     * Bind parameters to statement
     *
     * @param Statement       $stmt
     * @param ArrayCollection $parameters
     */
    public function bindParameters(Statement $stmt, ArrayCollection $parameters)
    {
        $values = [];
        $types  = [];
        foreach ($parameters as $parameter) {
            /* @var $parameter Parameter */
            $values[] = $parameter->getValue();
            $types[]  = $parameter->getType();
        }
        $typeOffset = array_key_exists(0, $types) ? -1 : 0;
        $bindIndex  = 1;

        foreach ($values as $value) {
            $typeIndex = $bindIndex + $typeOffset;
            if (isset($types[$typeIndex])) {
                $type = $types[$typeIndex];
                $stmt->bindValue($bindIndex, $value, $type);
            } else {
                $stmt->bindValue($bindIndex, $value);
            }
            ++$bindIndex;
        }
    }
}
