<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchBefore;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller to handle organization switching.
 */
class SwitchOrganizationController
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var TranslatorInterface */
    private $translator;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route(
     *      "/switch-organization/{id}.{_format}",
     *      name="oro_security_switch_organization",
     *      requirements={"id"="\d+", "_format"="html|json"},
     *      defaults={"id"="0", "_format" = "html"}
     * )
     */
    public function switchOrganizationAction(Organization $organization, Request $request): Response
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof OrganizationAwareTokenInterface) {
            throw $this->createOrganizationAccessDeniedException($organization);
        }

        if (!$organization->isEnabled()) {
            throw $this->createOrganizationAccessDeniedException($organization);
        }

        $user = $token->getUser();
        if (!$user instanceof User || !$user->isBelongToOrganization($organization)) {
            throw $this->createOrganizationAccessDeniedException($organization);
        }

        $event = new OrganizationSwitchBefore($user, $token->getOrganization(), $organization);
        $this->eventDispatcher->dispatch($event, OrganizationSwitchBefore::NAME);
        $organization = $event->getOrganizationToSwitch();
        if (!$user->isBelongToOrganization($organization, true)) {
            throw $this->createOrganizationAccessDeniedException($organization);
        }

        $token->setOrganization($organization);
        $this->eventDispatcher->dispatch(
            new OrganizationSwitchAfter($user, $organization),
            OrganizationSwitchAfter::NAME
        );

        if ('html' !== $request->getRequestFormat()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $request->attributes->set('_fullRedirect', true);

        return new RedirectResponse($this->urlGenerator->generate('oro_default'));
    }

    private function createOrganizationAccessDeniedException(Organization $organization): AccessDeniedException
    {
        return new AccessDeniedException(
            $this->translator->trans(
                'oro.security.organization.access_denied',
                ['%organization_name%' => $organization->getName()]
            )
        );
    }
}
