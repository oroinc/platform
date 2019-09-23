<?php

namespace Oro\Bundle\DistributionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * The package manager security controller.
 */
class SecurityController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            AuthenticationUtils::class
        ]);
    }

    /**
     * @Route("/login")
     * @Template("OroDistributionBundle:Security:login.html.twig")
     */
    public function loginAction()
    {
        return [
            // last username entered by the user (if any)
            'last_username' => $this->get(AuthenticationUtils::class)->getLastUsername(),
            // last authentication error (if any)
            'error'         => $this->get(AuthenticationUtils::class)->getLastAuthenticationError()
        ];
    }
}
