<?php

namespace Oro\Bundle\DistributionBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityController extends Controller
{
    /**
     * @Route("/login")
     * @Template("OroDistributionBundle:Security:login.html.twig")
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                SecurityContext::AUTHENTICATION_ERROR
            );
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR, $session->get(SecurityContext::ACCESS_DENIED_ERROR));
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return ['last_username' => $session->get(SecurityContext::LAST_USERNAME), 'error' => $error];
    }
}
