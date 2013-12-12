<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/{bundle}/{name}/{_format}",
     *      name="oro_dashboard_widget",
     *      requirements={"bundle"="\w+", "name"="\w+", "_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     */
    public function widgetAction($bundle, $name)
    {
        return $this->render(
            sprintf('%s:Dashboard:%s.html.twig', $bundle, $name)
        );
    }
}
