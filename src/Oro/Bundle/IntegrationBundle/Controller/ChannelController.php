<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\Rest\Util\Codes;
use JMS\JobQueueBundle\Entity\Job;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler;

/**
 * @Route("/channel")
 */
class ChannelController extends Controller
{
    /**
     * @Route("/", name="oro_integration_channel_index")
     * @Acl(
     *      id="oro_integration_channel_index",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroIntegrationBundle:Channel"
     * )
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/create", name="oro_integration_channel_create")
     * @Acl(
     *      id="oro_integration_channel_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroIntegrationBundle:Channel"
     * )
     * @Template("OroIntegrationBundle:Channel:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Channel());
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}), name="oro_integration_channel_update")
     * @Acl(
     *      id="oro_integration_channel_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroIntegrationBundle:Channel"
     * )
     * @Template()
     */
    public function updateAction(Channel $channel)
    {
        return $this->update($channel);
    }

    /**
     * @Route("/schedule/{id}", requirements={"id"="\d+"}), name="oro_integration_channel_schedule")
     * @AclAncestor("oro_integration_channel_update")
     */
    public function scheduleAction(Channel $channel)
    {
        $job = new Job(SyncCommand::COMMAND_NAME, ['--channel-id=' . $channel->getId(), '-v']);

        $error = false;
        try {
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($job);
            $em->flush($job);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $status = $error === false ? Codes::HTTP_OK : Codes::HTTP_BAD_REQUEST;

        return new JsonResponse(['job_id' => $job->getId()], $status);
    }

    /**
     * @param Channel $channel
     *
     * @return array
     */
    protected function update(Channel $channel)
    {
        if ($this->get('oro_integration.form.handler.channel')->process($channel)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.integration.controller.channel.message.saved')
            );

            return $this->get('oro_ui.router')->redirectAfterSave(
                ['route' => 'oro_integration_channel_update', 'parameters' => ['id' => $channel->getId()]],
                ['route' => 'oro_integration_channel_index'],
                $channel
            );
        }
        $form = $this->getForm();

        return [
            'form' => $form->createView()
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
}
