<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * @Route("/schedule/{id}", requirements={"id"="\d+"}, name="oro_integration_schedule", methods={"POST"})
     * @AclAncestor("oro_integration_update")
     * @CsrfProtection()
     */
    public function scheduleAction(Integration $integration, Request $request)
    {
        if ($integration->isEnabled()) {
            try {
                $this->get('oro_integration.genuine_sync_scheduler')->schedule(
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
                    'message'    => $this->get('translator')->trans(
                        'oro.integration.scheduled',
                        ['%url%' => $checkJobProgressUrl]
                    )
                ];
            } catch (\Exception $e) {
                $this->get('logger')->error(
                    sprintf(
                        'Failed to schedule integration synchronization. Integration Id: %s.',
                        $integration->getId()
                    ),
                    ['e' => $e]
                );

                $status = Response::HTTP_BAD_REQUEST;
                $response = [
                    'successful' => false,
                    'message'    => $this->get('translator')->trans('oro.integration.sync_error')
                ];
            }
        } else {
            $status = Response::HTTP_OK;
            $response = [
                'successful' => false,
                'message'    => $this->get('translator')->trans('oro.integration.sync_error_integration_deactivated')
            ];
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
        $formHandler = $this->get('oro_integration.form.handler.integration');

        if ($formHandler->process($integration)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.integration.controller.integration.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($integration);
        }

        $form = $formHandler->getForm();

        return [
            'entity' => $integration,
            'form'   => $form->createView()
        ];
    }
}
