<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Model\CommentCountAmountInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class EmailCommentsProvider implements CommentCountAmountInterface
{
    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var EntityManager */
    protected $em;

    /** @var aclHelper */
    protected $aclHelper;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    public function __construct(
        EmailThreadProvider $emailThreadProvider,
        EntityRoutingHelper $entityRoutingHelper,
        Registry $doctrine,
        AclHelper $aclHelper
    ) {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->em = $doctrine->getManager();
        $this->aclHelper  = $aclHelper;
    }

    /**
     * @param $entityClass
     * @param $entityId
     * @return int
     */
    public function getAmount($entityClass, $entityId)
    {
        $entityRoutingHelper = $this->entityRoutingHelper;
        $entity = $entityRoutingHelper->getEntity($entityClass, $entityId);
        $relatedEmails = $this->emailThreadProvider->getThreadEmails($this->em, $entity);
        $fieldName = ExtendHelper::buildAssociationName($entityClass);
        $repository = $this->em->getRepository(Comment::ENTITY_NAME);

        $count = 0;
        foreach ($relatedEmails as $relatedEmail) {
            $qb         = $repository->getNumberOfComment($fieldName, $relatedEmail->getId());
            $query      = $this->aclHelper->apply($qb);
            $count += (int) $query->getSingleScalarResult();
        }

        return $count;
    }
}
