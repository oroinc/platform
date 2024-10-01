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
 * Modifies the email query to return only emails that are belong to allowed email users.
 */
class ProtectEmailQueryByAcl implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
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
        $qb->andWhere($qb->expr()->exists(
            $this->doctrineHelper->createQueryBuilder(EmailUser::class, $emailUserAlias)
                ->where(sprintf('%s.email = %s', $emailUserAlias, $rootAlias))
                ->getDQL()
        ));
    }
}
