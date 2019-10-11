<?php

namespace Oro\Bundle\DistributionBundle\Controller;

use Oro\Bundle\DistributionBundle\Entity\Composer\Config;
use Oro\Bundle\DistributionBundle\Form\Type\Composer\ConfigType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The package manager settings controller.
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

        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        $saved = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $config->flush();
            $saved = true;
        }

        return [
            'form' => $form->createView(),
            'saved' => $saved
        ];
    }
}
