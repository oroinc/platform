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
     * @Route("/connection/check", name="oro_imap_gmail_connection_check", methods={"GET", "POST"})
     */
    public function checkAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $connectionManager = $this->container->get('oro_imap.manager.controller.connection');

        try {
            $form = $connectionManager->getFormCheckGmailConnection($request);

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
