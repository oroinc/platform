<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Api\Repository\EmailOriginRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks that API email origin exists in the database and create it if it does not exist yet.
 */
class LoadEmailOrigin implements ProcessorInterface
{
    public const EMAIL_ORIGIN = '_email_origin';

    private EmailOriginRepository $emailOriginRepository;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(EmailOriginRepository $emailOriginRepository, TokenAccessorInterface $tokenAccessor)
    {
        $this->emailOriginRepository = $emailOriginRepository;
        $this->tokenAccessor = $tokenAccessor;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if ($context->has(self::EMAIL_ORIGIN)) {
            return;
        }

        if (!$context->getForm()->isValid()) {
            return;
        }

        $context->set(
            self::EMAIL_ORIGIN,
            $this->emailOriginRepository->getEmailOrigin(
                $this->tokenAccessor->getOrganizationId(),
                $this->tokenAccessor->getUserId()
            )
        );
    }
}
