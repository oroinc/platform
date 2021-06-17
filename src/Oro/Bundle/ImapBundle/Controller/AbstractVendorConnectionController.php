<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract controller for OAuth connection check controllers
 */
abstract class AbstractVendorConnectionController extends AbstractController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    protected function check(Request $request): JsonResponse
    {
        $connectionControllerManager = $this->get(ConnectionControllerManager::class);
        $formParentName = $request->get('formParentName');

        try {
            $form = $connectionControllerManager->getCheckConnectionForm($request, $formParentName);

            $response = [
                'html' => $this->renderView('@OroImap/Connection/checkAuthorized.html.twig', [
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
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    protected function handleAccessToken(Request $request, string $type): JsonResponse
    {
        $connectionControllerManager = $this->get(ConnectionControllerManager::class);
        try {
            $code = $request->get('code');
            if ($code) {
                $response = $connectionControllerManager
                    ->getAccessToken($code, $type);
            } else {
                $response = [
                    'error' => $request->get('error_description')
                            ?: $this->get(TranslatorInterface::class)->trans('oro.imap.oauth.manager.token.error')
                    ];
            }
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage()
            ];
        }

        return new JsonResponse($response);
    }

    /**
     * @return RequestStack
     */
    protected function getRequestStack(): RequestStack
    {
        return $this->get('request_stack');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                ConnectionControllerManager::class,
            ]
        );
    }
}
