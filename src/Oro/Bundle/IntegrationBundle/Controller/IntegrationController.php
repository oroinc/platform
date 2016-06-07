<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Util\Codes;

use JMS\JobQueueBundle\Entity\Job;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler;
use Symfony\Component\HttpFoundation\Request;

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
        
        $status  = Codes::HTTP_OK;
        $response = [
            'successful' => true,
            'message'    => '',
        ];

        try {
            $job = $this->getSyncScheduler()->schedule($integration, (bool) $request->get('force', false));
            
            $jobViewLink = sprintf(
                '<a href="%s" class="job-view-link">%s</a>',
                $this->get('router')->generate('oro_cron_job_view', ['id' => $job->getId()]),
                $this->get('translator')->trans('oro.integration.progress')
            );

            $response['message'] = str_replace(
                '{{ job_view_link }}',
                $jobViewLink,
                $this->get('translator')->trans('oro.integration.sync')
            );
            $response['job_id'] = $job->getId();
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
}
