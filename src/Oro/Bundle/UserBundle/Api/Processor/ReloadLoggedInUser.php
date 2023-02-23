<?php

namespace Oro\Bundle\UserBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Reloads the current logged in User entity in case it cannot be updated
 * due to a validation constraint violation.
 * It is required to avoid invalid User entity in the security context
 * that can lead breaking of a logic that is executed after API processors finished.
 * For example, when API is executed as AJAX request the security context
 * is stored in the session at the end of each request,
 * {@see \Symfony\Component\Security\Http\Firewall\ContextListener::onKernelResponse}.
 */
class ReloadLoggedInUser implements ProcessorInterface
{
    private BaseUserManager $userManager;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(BaseUserManager $userManager, TokenAccessorInterface $tokenAccessor)
    {
        $this->userManager = $userManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!$form->isSubmitted() || $form->isValid()) {
            return;
        }

        $user = $context->getData();
        if ($this->tokenAccessor->getUser() === $user) {
            $this->userManager->reloadUser($user);
        }
    }
}
