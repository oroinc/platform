<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="oro_user_security_login")
     * @Template
     */
    public function loginAction()
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        // 302 redirect does not processed by Backbone.sync handler, but 401 error does.
        if ($request->isXmlHttpRequest()) {
            return new Response(null, 401);
        }

        $helper           = $this->get('security.authentication_utils');
        $csrfTokenManager = $this->get('security.csrf.token_manager');

        return [
            // last username entered by the user (if any)
            'last_username' => $helper->getLastUsername(),
            // last authentication error (if any)
            'error'         => $helper->getLastAuthenticationError(),
            // CSRF token for the login form
            'csrf_token'    => $csrfTokenManager->getToken('authenticate')->getValue()
        ];
    }

    /**
     * @Route("/login-check", name="oro_user_security_check")
     */
    public function checkAction()
    {
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
