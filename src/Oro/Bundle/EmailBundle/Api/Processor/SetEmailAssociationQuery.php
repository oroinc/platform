<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a query for "email" association of EmailAttachment entity.
 */
class SetEmailAssociationQuery implements ProcessorInterface
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
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        $emailField = $definition->getField('email');
        if (null !== $emailField && !$emailField->isExcluded() && null === $emailField->getAssociationQuery()) {
            $emailField->setAssociationQuery(
                $this->doctrineHelper
                    ->createQueryBuilder(Email::class, 'r')
                    ->innerJoin('r.emailBody', 'b')
                    ->innerJoin(EmailAttachment::class, 'e', Join::WITH, 'e MEMBER OF b.attachments')
            );
        }
    }
}
