<?php

namespace Oro\Bundle\DistributionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\DistributionBundle\Entity\Composer\Config;
use Oro\Bundle\HelpBundle\Annotation\Help;

/**
 * @Help(link="https://www.orocommerce.com/documentation/current/install-upgrade")
 */
class SettingsController extends Controller
{
    /**
     * @Route("/settings")
     * @Template("OroDistributionBundle:Settings:index.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $config = new Config($this->get('oro_distribution.composer.json_file'));

        $form = $this->createForm('oro_composer_config', $config);
        $form->handleRequest($request);

        $saved = false;
        if ($form->isValid()) {
            $config->flush();
            $saved = true;
        }

        return [
            'form' => $form->createView(),
            'saved' => $saved
        ];
    }
}
