<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ActivityBundle\Handler\ActivityEntityDeleteHandler;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EmailBundle\Api\Model\EmailThreadContextItemDelete;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Deletes an entity from an email thread context.
 */
class DeleteEmailThreadContextItem implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ActivityEntityDeleteHandler $deleteHandler;

    public function __construct(DoctrineHelper $doctrineHelper, ActivityEntityDeleteHandler $deleteHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var DeleteContext $context */

        if (!$context->hasResult()) {
            // result deleted or not supported
            return;
        }

        /** @var EmailThreadContextItemDelete $entity */
        $entity = $context->getResult();
        /** @var Email[] $threadEmails */
        $threadEmails = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->innerJoin(Email::class, 'p', Join::WITH, 'e.id = p.id OR e.thread = p.thread')
            ->where('p.id = :id')
            ->setParameter('id', $entity->getEmailEntity()->getId())
            ->getQuery()
            ->getResult();
        $hasChanges = false;
        foreach ($threadEmails as $email) {
            if ($this->deleteHandler->deleteActivityAssociation($email, $entity->getEntity(), false)) {
                $hasChanges = true;
            }
        }
        if (!$hasChanges) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }
        $this->doctrineHelper->getEntityManagerForClass(Email::class)->flush();

        $context->removeResult();
    }
}
