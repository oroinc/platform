<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Main security authentication controller
 */
class SecurityController extends AbstractController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            CsrfTokenManagerInterface::class,
            AuthenticationUtils::class,
            RequestStack::class
        ]);
    }

    #[Route(path: '/login', name: 'oro_user_security_login')]
    #[Template('@OroUser/Security/login.html.twig')]
    public function loginAction()
    {
        if ($this->getUser() instanceof AbstractUser) {
            return $this->redirect($this->generateUrl('oro_default'));
        }
        $request = $this->container->get(RequestStack::class)->getCurrentRequest();
        // 302 redirect does not processed by Backbone.sync handler, but 401 error does.
        if ($request->isXmlHttpRequest()) {
            return new Response(null, 401);
        }

        return [
            // last username entered by the user (if any)
            'last_username' => $this->container->get(AuthenticationUtils::class)->getLastUsername(),
            // last authentication error (if any)
            'error'         => $this->container->get(AuthenticationUtils::class)->getLastAuthenticationError(),
            // CSRF token for the login form
            'csrf_token'    => $this->container->get(CsrfTokenManagerInterface::class)->getToken('authenticate')
                ->getValue(),
        ];
    }

    #[Route(path: '/login-check', name: 'oro_user_security_check')]
    public function checkAction()
    {
        if ($this->getUser() instanceof AbstractUser) {
            return $this->redirect($this->generateUrl('oro_default'));
        }

        throw new \RuntimeException(
            'You must configure the check path to be handled by the firewall ' .
            'using form_login in your security firewall configuration.'
        );
    }

    #[Route(path: '/logout', name: 'oro_user_security_logout')]
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }
}
