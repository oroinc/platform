<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller to check OAuth connection for IMAP/SMTP.
 */
class CheckConnectionController extends AbstractController
{
    /**
     * @CsrfProtection()
     */
    public function checkAction(Request $request): JsonResponse
    {
        $formParentName = $request->get('formParentName');
        try {
            $form = $this->getConnectionManager()->getCheckConnectionForm($request, $formParentName);
            $response = [
                'html' => $this->renderView(
                    '@OroImap/Connection/checkAuthorized.html.twig',
                    ['form' => $form->createView()]
                )
            ];
        } catch (\Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return new JsonResponse($response);
    }

    private function getConnectionManager(): ConnectionControllerManager
    {
        return $this->get(ConnectionControllerManager::class);
    }

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ConnectionControllerManager::class,
            ]
        );
    }
}
