<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentProvider
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AttachmentConfig
     */
    protected $attachmentConfig;

    /**
     * @param EntityManager $entityManager
     * @param AttachmentConfig $attachmentConfig
     */
    public function __construct(EntityManager $entityManager, AttachmentConfig $attachmentConfig)
    {
        $this->em = $entityManager;
        $this->attachmentConfig = $attachmentConfig;
    }

    /**
     * @param object $entity
     *
     * @return Attachment[]
     */
    public function getEntityAttachments($entity)
    {
        if ($this->attachmentConfig->isAttachmentAssociationEnabled($entity)) {
            $className = ClassUtils::getClass($entity);

            $fieldName = ExtendHelper::buildAssociationName($className);
            $repo = $this->em->getRepository('OroAttachmentBundle:Attachment');

            $qb = $repo->createQueryBuilder('a');
            $qb->leftJoin('a.' . $fieldName, 'entity')
                ->where('entity.id = :entityId')
                ->setParameter('entityId', $entity->getId());

            return $qb->getQuery()->getResult();
        }

        return [];
    }
}
