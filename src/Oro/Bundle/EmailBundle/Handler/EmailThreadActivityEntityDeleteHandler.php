<?php

namespace Oro\Bundle\EmailBundle\Handler;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ActivityBundle\Handler\ActivityEntityDeleteHandler;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;

/**
 * The handler that is used by the old REST API to delete entities from email thread activity context.
 */
class EmailThreadActivityEntityDeleteHandler extends DeleteHandler
{
    private DoctrineHelper $doctrineHelper;
    private ActivityEntityDeleteHandler $activityEntityDeleteHandler;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActivityEntityDeleteHandler $activityEntityDeleteHandler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->activityEntityDeleteHandler = $activityEntityDeleteHandler;
    }

    #[\Override]
    public function handleDelete($id, ApiEntityManager $manager, array $options = []): void
    {
        /** @var RelationIdentifier $id */

        /** @var Email[] $threadEmails */
        $threadEmails = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->innerJoin(Email::class, 'p', Join::WITH, 'e.id = p.id OR e.thread = p.thread')
            ->where('p.id = :id')
            ->setParameter('id', $id->getOwnerEntityId())
            ->getQuery()
            ->getResult();
        foreach ($threadEmails as $email) {
            $this->activityEntityDeleteHandler->delete(
                new RelationIdentifier(
                    Email::class,
                    $email->getId(),
                    $id->getTargetEntityClass(),
                    $id->getTargetEntityId()
                ),
                false
            );
        }
        $this->doctrineHelper->getEntityManagerForClass(Email::class)->flush();
    }
}
