<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Form\Type\TransportSelectType;

use OroCRM\Bundle\MagentoBundle\Entity\MagentoSoapTransport;

/**
 * @Route("/transport")
 */
class TransportController extends Controller
{
    /**
     * @Route("/list/{id}", requirements={"id"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template()
     */
    public function listAction(Channel $channel)
    {
        return [
            'channel' => $channel
        ];
    }

    /**
     * @Route("/prepare/{channelType}", requirements={"channelType"="\w+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template()
     */
    public function prepareAction($channelType, Request $request)
    {
        $form = $this->getForm($channelType);
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $url = $this->generateUrl(
                    'oro_integration_transport_create',
                    [
                        'channelType'      => $channelType,
                        'transportType'    => $form->get(TransportSelectType::TYPE_FIELD)->getData(),
                        '_widgetContainer' => $request->get('_widgetContainer'),
                        '_wid'             => $request->get('_wid'),
                    ]
                );

                return $this->redirect($url);
            }
        }

        return [
            'form'        => $form->createView(),
            'channelType' => $channelType
        ];
    }

    /**
     * @Route("/create/{transportType}/{channelType}", requirements={"transportType"="\w+", "channelType"="\w+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template("OroIntegrationBundle:Transport:update.html.twig")
     */
    public function createAction($transportType, $channelType)
    {
        // @TODO find transport by type
        $transport = new MagentoSoapTransport();

        return $this->update($transport, $transportType, $channelType);
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template
     */
    public function updateAction(Transport $transport)
    {
        $registry = $this->get('oro_integration.manager.types_registry');
        // @TODO get types by entity
        $transportType = '';
        $channelType   = '';

        return $this->update($transport, $transportType, $channelType);
    }

    /**
     * @param Transport $transport
     * @param string    $transportType
     * @param string    $channelType
     *
     * @return array
     */
    protected function update(Transport $transport, $transportType, $channelType)
    {
        $registry = $this->get('oro_integration.manager.types_registry');

        $formType = $registry->getTransportType($channelType, $transportType)->getSettingsFormType();
        $form     = $this->get('form.factory')->create($formType, $transport);
        $saved    = false;

        $request = $this->get('request');
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $em = $this->get('doctrine.orm.entity_manager');
                $em->persist($transport);
                $em->flush();

                $saved = true;
            }
        }

        return [
            'saved' => $saved,
            'form'  => $form->createView()
        ];
    }

    /**
     * Return "select transport type" form
     *
     * @param string $channelType
     *
     * @return FormInterface
     */
    protected function getForm($channelType)
    {
        $form = $this->get('form.factory')->create(
            'oro_integration_transport_select_form',
            null,
            [TransportSelectType::TYPE_OPTION => $channelType]
        );

        return $form;
    }
}
