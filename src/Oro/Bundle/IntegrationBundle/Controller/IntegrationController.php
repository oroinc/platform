<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UIBundle\Route\Router;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for Integrations config page
 */
#[Route(path: '/integration')]
class IntegrationController extends AbstractController
{
    use RequestHandlerTrait;

    #[Route(path: '/', name: 'oro_integration_index')]
    #[Template]
    #[Acl(id: 'oro_integration_view', type: 'entity', class: Integration::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [
            'entity_class' => Integration::class
        ];
    }

    #[Route(path: '/create', name: 'oro_integration_create')]
    #[Template('@OroIntegration/Integration/update.html.twig')]
    #[Acl(id: 'oro_integration_create', type: 'entity', class: Integration::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new Integration(), $request);
    }

    #[Route(path: '/update/{id}', name: 'oro_integration_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_integration_update', type: 'entity', class: Integration::class, permission: 'EDIT')]
    public function updateAction(Request $request, Integration $integration)
    {
        return $this->update($integration, $request);
    }

    #[Route(
        path: '/schedule/{id}',
        name: 'oro_integration_schedule',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[AclAncestor('oro_integration_update')]
    #[CsrfProtection()]
    public function scheduleAction(Integration $integration, Request $request)
    {
        if ($integration->isEnabled()) {
            try {
                $this->container->get(GenuineSyncScheduler::class)->schedule(
                    $integration->getId(),
                    null,
                    ['force' => (bool)$request->get('force', false)]
                );
                $checkJobProgressUrl = $this->generateUrl(
                    'oro_message_queue_root_jobs',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $status = Response::HTTP_OK;
                $response = [
                    'successful' => true,
                    'message'    => $this->container->get(TranslatorInterface::class)->trans(
                        'oro.integration.scheduled',
                        ['%url%' => $checkJobProgressUrl]
                    )
                ];
            } catch (\Exception $e) {
                $this->container->get(LoggerInterface::class)->error(
                    sprintf(
                        'Failed to schedule integration synchronization. Integration Id: %s.',
                        $integration->getId()
                    ),
                    ['e' => $e]
                );

                $status = Response::HTTP_BAD_REQUEST;
                $response = [
                    'successful' => false,
                    'message'    => $this->getTranslator()->trans('oro.integration.sync_error')
                ];
            }
        } else {
            $status = Response::HTTP_OK;
            $response = [
                'successful' => false,
                'message'    => $this->getTranslator()->trans('oro.integration.sync_error_integration_deactivated')
            ];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * @param Integration $integration
     *
     * @return array
     */
    protected function update(Integration $integration, Request $request)
    {
        $formHandler = $this->container->get(ChannelHandler::class);

        if ($formHandler->process($integration)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->getTranslator()->trans('oro.integration.controller.integration.message.saved')
            );

            return $this->container->get(Router::class)->redirect($integration);
        }

        $form = $formHandler->getForm();

        return [
            'entity' => $integration,
            'form'   => $form->createView()
        ];
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                GenuineSyncScheduler::class,
                LoggerInterface::class,
                ChannelHandler::class,
                Router::class,
            ]
        );
    }
}
