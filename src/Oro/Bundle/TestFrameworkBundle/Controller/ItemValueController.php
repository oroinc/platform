<?php

namespace Oro\Bundle\TestFrameworkBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ItemValueController extends Controller
{
    /**
     * @Route("/", name="oro_test_item_value_index")
     */
    public function indexAction()
    {
        return new Response();
    }

    /**
     * @Route("/view/{id}", name="oro_test_item_value_view", requirements={"id"="\d+"})
     */
    public function viewAction()
    {
        return new Response();
    }

    /**
     * @Route("/update/{id}", name="oro_test_item_value_update", requirements={"id"="\d+"})
     */
    public function updateAction()
    {
        return new Response();
    }
}
