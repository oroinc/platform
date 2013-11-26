<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler;

/**
 * @Route("/channel")
 */
class ChannelController extends Controller
{
    /**
     * @Route("/index")
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
     * @Route("/create")
     * @Acl(
     *      id="oro_integration_channel_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroIntegrationBundle:Channel"
     * )
     */
    public function createAction()
    {
        return $this->renderTemplate($this->update(new Channel()));
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}))
     * @Acl(
     *      id="oro_integration_channel_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroIntegrationBundle:Channel"
     * )
     */
    public function updateAction(Channel $channel)
    {
        return $this->renderTemplate($this->update($channel));
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

            return $this->get('oro_ui.router')->actionRedirect(
                array(
                    'route'      => 'oro_integration_channel_update',
                    'parameters' => array('id' => $channel->getId()),
                ),
                array(
                    'route' => 'oro_integration_channel_index',
                )
            );
        }

        return [
            'form' => $this->get('oro_integration.form.channel')->createView()
        ];
    }

    /**
     * Render needed template
     *
     * @param array $context
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderTemplate($context)
    {
        $isUpdateHandler = $this->get('request')->get(ChannelHandler::UPDATE_MARKER, false);
        $template        = 'OroIntegrationBundle:Channel:update.html.twig';
        if ($isUpdateHandler !== false) {
            $contentBlock = $this->get('twig')->loadTemplate($template)->renderBlock('page_container', $context);

            return new JsonResponse(['content' => $contentBlock]);
        } else {
            return $this->render($template, $context);
        }
    }
}
