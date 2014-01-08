<?php

namespace Oro\Bundle\DistributionBundle\Controller;

use Oro\Bundle\DistributionBundle\Form\Type\Composer\ConfigType;
use Oro\Bundle\DistributionBundle\Entity\Composer\Config;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class SettingsController extends Controller
{
    /**
     * @Route("/settings")
     * @Template("OroDistributionBundle:Settings:index.html.twig")
     */
    public function indexAction()
    {
        $config = new Config($this->get('oro_distribution.composer.json_file'));

        $form = $this->createForm('oro_composer_config', $config);
        return [
            'form' => $form->createView()
        ];
    }
}
