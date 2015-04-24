<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CommentBundle\Model\CommentLogicGetCountInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProviderCommentLogicGetCount implements CommentLogicGetCountInterface
{
    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var EntityManager */
    protected $om;

    /** @var aclHelper */
    protected $aclHelper;

    public function __construct(
        EmailThreadProvider $emailThreadProvider,
        ObjectManager $om,
        AclHelper $aclHelper
    ) {
        $this->emailThreadProvider  = $emailThreadProvider;
        $this->om  = $om;
        $this->aclHelper  = $aclHelper;
    }


    public function getCount($entityClass, $entityId)
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $entity = $entityRoutingHelper->getEntity($entityClass, $entityId);

        $relatedEmails = $this->emailThreadProvider->getThreadEmails($this->om, $entity);

        $fieldName = ExtendHelper::buildAssociationName($entityClass);
        $repository = $this->om->getRepository($entityClass);
        $count = 0;
        foreach ($relatedEmails as $relatedEmail) {
            $qb         = $repository->getNumberOfComment($fieldName, $relatedEmail->getId());
            $query      = $this->aclHelper->apply($qb);
            $count .= (int) $query->getSingleScalarResult();
        }

        return $count;
    }
}
