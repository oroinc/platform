<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies the email attachment query to return only attachments that are belong to allowed email users.
 */
class ProtectEmailAttachmentQueryByAcl implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasQuery()) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $context->getQuery();
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($qb);
        $emailUserAlias = $rootAlias . '_email_users';
        $emailBodyAlias = $rootAlias . '_email_body';
        $emailAlias = $rootAlias . '_email';
        $qb->andWhere($qb->expr()->exists(
            $this->doctrineHelper->createQueryBuilder(EmailUser::class, $emailUserAlias)
                ->innerJoin(sprintf('%s.email', $emailUserAlias), $emailAlias)
                ->innerJoin(sprintf('%s.emailBody', $emailAlias), $emailBodyAlias)
                ->where(sprintf('%s MEMBER OF %s.attachments', $rootAlias, $emailBodyAlias))
                ->getDQL()
        ));
    }
}
