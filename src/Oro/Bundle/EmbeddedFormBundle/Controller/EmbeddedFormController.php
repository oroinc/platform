<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class EmbeddedFormController extends Controller
{
    /**
     * @Route(name="oro_embedded_form_list")
     * @Template
     */
    public function indexAction()
    {
        return [];
    }
} 