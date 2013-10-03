<?php

namespace Oro\Bundle\CrowdinBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/hello/{name}")
     */
    public function indexAction($name)
    {
        $adapter = $this->get('oro_crowdin.adapter');
        $adapter->addFile('@./../oro.yml');
    }
}
