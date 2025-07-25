<?php

namespace Oro\Bundle\TestFrameworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Workflow aware entity controller.
 */
class WorkflowAwareEntityController extends AbstractController
{
    #[Route(path: '/', name: 'oro_test_wfa_index')]
    public function indexAction()
    {
        return new Response();
    }

    #[Route(path: '/view/{id}', name: 'oro_test_wfa_view', requirements: ['id' => '\d+'])]
    public function viewAction()
    {
        return new Response();
    }

    #[Route(path: '/create', name: 'oro_test_wfa_create')]
    public function createAction()
    {
        return new Response();
    }

    #[Route(path: '/update/{id}', name: 'oro_test_wfa_update', requirements: ['id' => '\d+'])]
    public function updateAction()
    {
        return new Response();
    }

    #[Route(path: '/delete/{id}', name: 'oro_test_wfa_delete', requirements: ['id' => '\d+'])]
    public function deleteAction()
    {
        return new Response();
    }
}
