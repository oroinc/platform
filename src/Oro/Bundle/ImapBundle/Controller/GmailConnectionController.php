<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/gmail")
 */
class GmailConnectionController extends Controller
{
    /**
     * @Route("/connection/check", name="oro_imap_gmail_connection_check", methods={"POST"})
     */
    public function checkAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $connectionControllerManager = $this->container->get('oro_imap.manager.controller.connection');
        $formParentName = $request->get('formParentName');

        try {
            $form = $connectionControllerManager->getFormCheckGmailConnection($request, $formParentName);

            $response = [
                'html' => $this->renderView('OroImapBundle:Connection:checkGmail.html.twig', [
                    'form' => $form->createView(),
                ])
            ];
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage()
            ];
        }

        return new JsonResponse($response);
    }
}
