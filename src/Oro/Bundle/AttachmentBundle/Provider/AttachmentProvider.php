<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentProvider
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param object $entity
     *
     * @return Attachment[]
     */
    public function getEntityAttachments($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        if (!(new Attachment())->supportTarget($entityClass)) {
            return [];
        }

        $fieldName = ExtendHelper::buildAssociationName($entityClass);
        $repo = $this->em->getRepository('OroAttachmentBundle:Attachment');

        $qb = $repo->createQueryBuilder('a');
        $qb->leftJoin('a.' . $fieldName, 'entity')
            ->where('entity.id = :entityId')
            ->setParameter('entityId', $entity->getId());

        return $qb->getQuery()->getResult();
    }
}
