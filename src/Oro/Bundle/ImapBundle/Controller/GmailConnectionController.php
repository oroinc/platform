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
            $form = $connectionControllerManager->getCheckGmailConnectionForm($request, $formParentName);

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

    /**
     * @Route("/connection/access-token", name="oro_imap_gmail_access_token", methods={"POST"})
     */
    public function accessTokenAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $connectionControllerManager = $this->container->get('oro_imap.manager.controller.connection');
        try {
            $response = $connectionControllerManager->getAccessToken($request->get('code'));
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage()
            ];
        }

        return new JsonResponse($response);
    }
}
