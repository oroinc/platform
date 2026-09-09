<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The controller to check OAuth connection for IMAP/SMTP.
 */
class CheckConnectionController extends AbstractController
{
    #[CsrfProtection()]
    public function checkAction(Request $request, string $accountType): JsonResponse
    {
        $formParentName = $request->get('formParentName');
        try {
            $form = $this->getConnectionManager()->getCheckConnectionForm(
                $request,
                $formParentName,
                $accountType
            );
            $response = [
                'html' => $this->renderView(
                    '@OroImap/Connection/checkAuthorized.html.twig',
                    ['form' => $form->createView()]
                )
            ];
        } catch (AccessDeniedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return new JsonResponse($response);
    }

    private function getConnectionManager(): ConnectionControllerManager
    {
        return $this->container->get(ConnectionControllerManager::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ConnectionControllerManager::class,
            ]
        );
    }
}
