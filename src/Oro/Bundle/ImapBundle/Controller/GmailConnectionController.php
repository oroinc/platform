<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;

/**
 * @Route("/gmail")
 */
class GmailConnectionController extends Controller
{
    /**
     * @var ImapEmailFolderManager
     */
    protected $manager;

    /**
     * @Route("/connection/check", name="oro_imap_gmail_connection_check", methods={"GET", "POST"})
     */
    public function checkAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $connectionManager = $this->container->get('oro_imap.manager.controller.connection');
        $form = $connectionManager->getFormCheckGmailConnection($request->get('accessToken'));

        $response = [
            'html' => $this->renderView('OroImapBundle:Connection:checkGmail.html.twig', [
                'form' => $form->createView(),
            ])
        ];

        return new JsonResponse($response);
    }
}
