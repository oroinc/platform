<?php

namespace Oro\Bundle\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

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
        $attemptsProvider = $this->get('oro_user.security.login_attempts_provider');
        $userManager      = $this->get('oro_user.manager');

        $remainingAttempts = null;
        if ($attemptsProvider->hasLimit()
            && $helper->getLastAuthenticationError(false) instanceof BadCredentialsException
            && $helper->getLastUsername()
        ) {
            if ($user = $userManager->findUserByUsernameOrEmail($helper->getLastUsername())) {
                $remainingAttempts = $attemptsProvider->getRemaining($user);
            }
        }

        return [
            // last username entered by the user (if any)
            'last_username' => $helper->getLastUsername(),
            // last authentication error (if any)
            'error'         => $helper->getLastAuthenticationError(),
            // CSRF token for the login form
            'csrf_token'    => $csrfTokenManager->getToken('authenticate')->getValue(),
            // Remaining login attempts
            'remaining_attempts' => $remainingAttempts,
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
