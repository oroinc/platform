<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Controller\Configuration\MailboxController;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MailboxAuthorizationListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param ManagerRegistry               $registry
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     */
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
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
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

    /**
     * @return OrganizationRepository
     */
    protected function getOrganizationRepository()
    {
        return $this->registry->getRepository('OroOrganizationBundle:Organization');
    }

    /**
     * @return MailboxRepository
     */
    protected function getMailboxRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:Mailbox');
    }
}
