<?php

namespace Oro\Bundle\TestFrameworkBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class WorkflowAwareEntityController extends Controller
{
    /**
     * @Route("/", name="oro_test_wfa_index")
     */
    public function indexAction()
    {
        return new Response();
    }

    /**
     * @Route("/view/{id}", name="oro_test_wfa_view", requirements={"id"="\d+"})
     */
    public function viewAction()
    {
        return new Response();
    }

    /**
     * @Route("/create", name="oro_test_wfa_create",)
     */
    public function createAction()
    {
        return new Response();
    }

    /**
     * @Route("/update/{id}", name="oro_test_wfa_update", requirements={"id"="\d+"})
     */
    public function updateAction()
    {
        return new Response();
    }

    /**
     * @Route("/delete/{id}", name="oro_test_wfa_delete", requirements={"id"="\d+"})
     */
    public function deleteAction()
    {
        return new Response();
    }
}
