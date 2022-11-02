<?php

namespace Oro\Bundle\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Main security authentication controller
 */
class SecurityController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            CsrfTokenManagerInterface::class,
            AuthenticationUtils::class,
            RequestStack::class
        ]);
    }

    /**
     * @Route("/login", name="oro_user_security_login")
     * @Template("@OroUser/Security/login.html.twig")
     */
    public function loginAction()
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('oro_default'));
        }
        $request = $this->get(RequestStack::class)->getCurrentRequest();
        // 302 redirect does not processed by Backbone.sync handler, but 401 error does.
        if ($request->isXmlHttpRequest()) {
            return new Response(null, 401);
        }

        return [
            // last username entered by the user (if any)
            'last_username' => $this->get(AuthenticationUtils::class)->getLastUsername(),
            // last authentication error (if any)
            'error'         => $this->get(AuthenticationUtils::class)->getLastAuthenticationError(),
            // CSRF token for the login form
            'csrf_token'    => $this->get(CsrfTokenManagerInterface::class)->getToken('authenticate')->getValue(),
        ];
    }

    /**
     * @Route("/login-check", name="oro_user_security_check")
     */
    public function checkAction()
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('oro_default'));
        }

        throw new \RuntimeException(
            'You must configure the check path to be handled by the firewall ' .
            'using form_login in your security firewall configuration.'
        );
    }

    /**
     * @Route("/logout", name="oro_user_security_logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }
}
