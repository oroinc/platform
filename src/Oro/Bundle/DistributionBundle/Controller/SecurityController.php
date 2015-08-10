<?php

namespace Oro\Bundle\DistributionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class SecurityController extends Controller
{
    /**
     * @Route("/login")
     * @Template("OroDistributionBundle:Security:login.html.twig")
     */
    public function loginAction()
    {
        $helper = $this->get('security.authentication_utils');

        return [
            // last username entered by the user (if any)
            'last_username' => $helper->getLastUsername(),
            // last authentication error (if any)
            'error'         => $helper->getLastAuthenticationError()
        ];
    }
}
