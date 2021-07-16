<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Controller\Configuration\MailboxController;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Verifies the access to current organization against update permission. If not granted, throws
 * access denied response to user.
 */
class MailboxAuthorizationListener
{
    protected ManagerRegistry $registry;

    protected AuthorizationCheckerInterface $authorizationChecker;

    protected TokenAccessorInterface $tokenAccessor;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->registry = $registry;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * Filters requests to MailboxController.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof MailboxController) {
            /*
             * Organization is determined as follows:
             *  - If mailbox is being deleted or updated (it is a part of route) then it's organization of mailbox.
             *  - If mailbox is not yet created and organization is passed through route, then it's that organization.
             *  - If mailbox is not yet created and organization is not passed in route, it's current organization of
             *    logged user.
             */
            $organization = null;
            if (null !== $mailboxId = $event->getRequest()->request->get('id')) {
                $mailbox = $this->getMailboxRepository()->find($mailboxId);
                if ($mailbox) {
                    $organization = $mailbox->getOrganization();
                }
            } elseif (null !== $organizationId = $event->getRequest()->request->get('organization_id')) {
                $organization = $this->getOrganizationRepository()->find($organizationId);
            } else {
                $organization = $this->tokenAccessor->getOrganization();
            }

            /*
             * Access to fetched organization is then verified against update permission. If it's not granted, return
             * access denied response to user.
             */
            if (!$organization || !$this->authorizationChecker->isGranted('oro_organization_update', $organization)) {
                throw new AccessDeniedHttpException();
            }
        }
    }

    protected function getOrganizationRepository(): OrganizationRepository
    {
        return $this->registry->getRepository('OroOrganizationBundle:Organization');
    }

    protected function getMailboxRepository(): MailboxRepository
    {
        return $this->registry->getRepository('OroEmailBundle:Mailbox');
    }
}
