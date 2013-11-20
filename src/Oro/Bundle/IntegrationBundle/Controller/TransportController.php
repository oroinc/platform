<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Form\Type\TransportSelectType;

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
        $registry = $this->get('oro_integration.manager.types_registry');

        $channelType = $channel->getType();
        $transports  = $channel->getTransports()->map(
            function (Transport $transport) use ($registry, $channelType) {
                return [
                    'id'    => $transport->getId(),
                    'label' => $transport->getLabel(),
                    'type'  => $registry->getTransportTypeBySettingEntity($transport, $channelType)->getLabel()
                ];
            }
        );

        return [
            'transports' => $transports
        ];
    }

    /**
     * @Route("/prepare/{channelType}/{channelId}", requirements={"channelType"="\w+", "channelId"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template()
     */
    public function prepareAction($channelType, $channelId, Request $request)
    {
        $isPost = false;
        $form   = $this->getForm($channelType);
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $isPost = true;
            }
        }

        return [
            'form'          => $form->createView(),
            'channelType'   => $channelType,
            'channelId'     => $channelId,
            'isPost'        => $isPost,
            'transportType' => $isPost ? $form->get(TransportSelectType::TYPE_FIELD)->getData() : null
        ];
    }

    /**
     * @Route(
     *      "/create/{transportType}/{channelType}/{id}",
     *      requirements={"transportType"="\w+", "channelType"="\w+", "id"="\d+"})
     * )
     * @AclAncestor("oro_integration_channel_update")
     * @Template("OroIntegrationBundle:Transport:update.html.twig")
     */
    public function createAction($transportType, $channelType, Channel $channel)
    {
        $registry = $this->get('oro_integration.manager.types_registry');

        $entityName = $registry->getTransportType($channelType, $transportType)->getSettingsEntityFQCN();
        /** @var Transport $transport */
        $transport = new $entityName();
        $transport->setChannel($channel);

        return $this->update($transport, $transportType, $channelType);
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template()
     */
    public function updateAction(Transport $transport)
    {
        $registry = $this->get('oro_integration.manager.types_registry');

        $channelType   = $transport->getChannel()->getType();
        $transportType = $registry->getTransportTypeBySettingEntity($transport, $channelType, true);

        return $this->update($transport, $transportType, $channelType);
    }

    /**
     * @Route("/delete/{id}", requirements={"id"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @param Transport $transport
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAction(Transport $transport)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $em->remove($transport);
        $em->flush();

        return new JsonResponse(null, 204);
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
