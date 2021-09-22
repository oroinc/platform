<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\UIBundle\Route\Router;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for Integrations config page
 *
 * @Route("/integration")
 */
class IntegrationController extends AbstractController
{
    use RequestHandlerTrait;

    /**
     * @Route("/", name="oro_integration_index")
     * @Acl(
     *      id="oro_integration_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroIntegrationBundle:Channel"
     * )
     * @Template()
     */
    public function indexAction()
    {
        return [
            'entity_class' => Integration::class
        ];
    }

    /**
     * @Route("/create", name="oro_integration_create")
     * @Acl(
     *      id="oro_integration_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroIntegrationBundle:Channel"
     * )
     * @Template("@OroIntegration/Integration/update.html.twig")
     */
    public function createAction(Request $request)
    {
        return $this->update(new Integration(), $request);
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}, name="oro_integration_update")
     * @Acl(
     *      id="oro_integration_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroIntegrationBundle:Channel"
     * )
     * @Template()
     */
    public function updateAction(Request $request, Integration $integration)
    {
        return $this->update($integration, $request);
    }

    /**
     * @Route("/schedule/{id}", requirements={"id"="\d+"}, name="oro_integration_schedule", methods={"POST"})
     * @AclAncestor("oro_integration_update")
     * @CsrfProtection()
     */
    public function scheduleAction(Integration $integration, Request $request)
    {
        if ($integration->isEnabled()) {
            try {
                $this->get(GenuineSyncScheduler::class)->schedule(
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
                    'message'    => $this->get(TranslatorInterface::class)->trans(
                        'oro.integration.scheduled',
                        ['%url%' => $checkJobProgressUrl]
                    )
                ];
            } catch (\Exception $e) {
                $this->get(LoggerInterface::class)->error(
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
        $formHandler = $this->get(ChannelHandler::class);

        if ($formHandler->process($integration)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->getTranslator()->trans('oro.integration.controller.integration.message.saved')
            );

            return $this->get(Router::class)->redirect($integration);
        }

        $form = $formHandler->getForm();

        return [
            'entity' => $integration,
            'form'   => $form->createView()
        ];
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->get(TranslatorInterface::class);
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
                GenuineSyncScheduler::class,
                LoggerInterface::class,
                ChannelHandler::class,
                Router::class,
            ]
        );
    }
}
