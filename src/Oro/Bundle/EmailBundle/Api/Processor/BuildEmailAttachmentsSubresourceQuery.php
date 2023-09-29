<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get "emailAttachments" association
 * for Email entity for "get_relationship" and "get_subresource" actions.
 */
class BuildEmailAttachmentsSubresourceQuery implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityIdHelper $entityIdHelper;

    public function __construct(DoctrineHelper $doctrineHelper, EntityIdHelper $entityIdHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $qb = $this->doctrineHelper->createQueryBuilder(EmailAttachment::class, 'e')
            ->innerJoin('e.emailBody', 'email_body')
            ->innerJoin(Email::class, 'email', Join::WITH, 'email.emailBody = email_body');
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $qb,
            $context->getParentId(),
            $context->getParentMetadata(),
            'email',
            AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME
        );
        $context->setQuery($qb);
    }
}
