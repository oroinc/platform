<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Psr\Log\LoggerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\PingableInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/integration")
 */
class IntegrationController extends Controller
{
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
            'entity_class' => $this->container->getParameter('oro_integration.entity.class')
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
     * @Template("OroIntegrationBundle:Integration:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Integration());
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
    public function updateAction(Integration $integration)
    {
        return $this->update($integration);
    }

    /**
     * @Route("/schedule/{id}", requirements={"id"="\d+"}, name="oro_integration_schedule")
     * @AclAncestor("oro_integration_update")
     */
    public function scheduleAction(Integration $integration, Request $request)
    {
        if (false === $integration->isEnabled()) {
            return new JsonResponse([
                'successful' => false,
                'message'    => $this->get('translator')->trans('oro.integration.sync_error_integration_deactivated'),
            ], Codes::HTTP_BAD_REQUEST);
        }

        /** @var TransportInterface $providerTransport */
        $providerTransport = $this->getTypeRegistry()->getTransportTypeBySettingEntity(
            $integration->getTransport(),
            $integration->getType()
        );

        if ($providerTransport instanceof PingableInterface) {
            try {
                $providerTransport->init($integration->getTransport());
                $isPingSuccessful = $providerTransport->ping();
            } catch (\Exception $e) {
                $isPingSuccessful = false;
                $this->getLogger()->error(
                    sprintf('[IntegrationSchedule] invalid credentials for integration %s', $integration->getId()),
                    ['integration' => $integration]
                );
            }

            if (false === $isPingSuccessful) {
                return new JsonResponse([
                    'successful' => false,
                    'message' => $this->get('translator')->trans('oro.integration.sync_error_invalid_credentials'),
                ], Codes::HTTP_BAD_REQUEST);
            }
        }

        $status  = Codes::HTTP_OK;
        $response = [
            'successful' => true,
            'message'    => $this->get('translator')->trans(
                'oro.integration.scheduled',
                ['%url%' => $this->generateUrl('oro_message_queue_root_jobs', [], UrlGeneratorInterface::ABSOLUTE_URL)]
            ),
        ];

        try {
            $this->getSyncScheduler()->schedule($integration->getId(), null, [
                'force' => (bool) $request->get('force', false)
            ]);
        } catch (\Exception $e) {
            $status  = Codes::HTTP_BAD_REQUEST;

            $response['successful'] = false;
            $response['message']    = sprintf(
                $this->get('translator')->trans('oro.integration.sync_error'),
                $e->getMessage()
            );
        }

        return new JsonResponse($response, $status);
    }

    /**
     * @param Integration $integration
     *
     * @return array
     */
    protected function update(Integration $integration)
    {
        if ($this->get('oro_integration.form.handler.integration')->process($integration)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.integration.controller.integration.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($integration);
        }
        $form = $this->getForm();
        
        return [
            'entity' => $integration,
            'form'   => $form->createView()
        ];
    }

    /**
     * Returns form instance
     *
     * @return FormInterface
     */
    protected function getForm()
    {
        $isUpdateOnly = $this->get('request')->get(ChannelHandler::UPDATE_MARKER, false);

        $form = $this->get('oro_integration.form.channel');
        // take different form due to JS validation should be shown even in case when it was not validated on backend
        if ($isUpdateOnly) {
            $form = $this->get('form.factory')
                ->createNamed('oro_integration_channel_form', 'oro_integration_channel_form', $form->getData());
        }

        return $form;
    }

    /**
     * @return GenuineSyncScheduler
     */
    protected function getSyncScheduler()
    {
        return $this->get('oro_integration.genuine_sync_scheduler');
    }

    /**
     * @return TypesRegistry
     */
    protected function getTypeRegistry()
    {
        return $this->get('oro_integration.manager.types_registry');
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->get('logger');
    }
}
