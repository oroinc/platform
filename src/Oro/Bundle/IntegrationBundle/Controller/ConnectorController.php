<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Connector;
use Oro\Bundle\IntegrationBundle\Form\Type\ConnectorSelectType;

/**
 * @Route("/connector")
 */
class ConnectorController extends Controller
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
        $connectors  = $channel->getConnectors()->map(
            function (Connector $connector) use ($registry, $channelType) {
                return [
                    'id'    => $connector->getId(),
                    'label' => $registry->getConnectorTypeBySettingEntity($connector, $channelType)->getLabel(),
                    'transportLabel' => $registry->getTransportTypeBySettingEntity(
                        $connector->getTransport(),
                        $channelType
                    )->getLabel()
                ];
            }
        );

        return [
            'connectors' => $connectors
        ];
    }

    /**
     * @Route("/prepare/{id}", requirements={"channelType"="\w+", "channelId"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template()
     */
    public function prepareAction(Channel $channel, Request $request)
    {
        $isPost = false;
        $form   = $this->getForm($channel);
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $isPost = true;
            }
        }

        return [
            'form'          => $form->createView(),
            'channelId'     => $channel->getId(),
            'channelType'   => $channel->getType(),
            'isPost'        => $isPost,
            'connectorType' => $isPost ? $form->get(ConnectorSelectType::TYPE_FIELD)->getData() : null
        ];
    }

    /**
     * @Route(
     *      "/create/{connectorType}/{channelType}/{id}",
     *      requirements={"connectorType"="\w+", "channelType"="\w+", "id"="\d+"})
     * )
     * @AclAncestor("oro_integration_channel_update")
     * @Template("OroIntegrationBundle:Connector:update.html.twig")
     */
    public function createAction($connectorType, $channelType, Channel $channel)
    {
        $registry = $this->get('oro_integration.manager.types_registry');

        $entityName = $registry->getConnectorType($channelType, $connectorType)->getSettingsEntityFQCN();
        /** @var Connector $connector */
        $connector = new $entityName();
        $connector->setChannel($channel);

        return $this->update($connector, $connectorType, $channelType);
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @Template()
     */
    public function updateAction(Connector $connector)
    {
        $registry = $this->get('oro_integration.manager.types_registry');

        $channelType   = $connector->getChannel()->getType();
        $connectorType = $registry->getConnectorTypeBySettingEntity($connector, $channelType, true);

        return $this->update($connector, $connectorType, $channelType);
    }

    /**
     * @Route("/delete/{id}", requirements={"id"="\d+"}))
     * @AclAncestor("oro_integration_channel_update")
     * @param Connector $connector
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAction(Connector $connector)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $em->remove($connector);
        $em->flush();

        return new JsonResponse(null, 204);
    }


    /**
     * @param Connector $connector
     * @param string    $connectorType
     * @param string    $channelType
     *
     * @return array
     */
    protected function update(Connector $connector, $connectorType, $channelType)
    {
        $registry = $this->get('oro_integration.manager.types_registry');

        $formType = $registry->getConnectorType($channelType, $connectorType)->getSettingsFormType();
        $form     = $this->get('form.factory')->create($formType, $connector, ['channel' => $connector->getChannel()]);
        $saved    = false;

        $request = $this->get('request');
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $em = $this->get('doctrine.orm.entity_manager');
                $em->persist($connector);
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
     * Return "select connector type" form
     *
     * @param Channel $channel
     *
     * @return FormInterface
     */
    protected function getForm(Channel $channel)
    {
        $channelType = $channel->getType();
        $registry    = $this->get('oro_integration.manager.types_registry');
        $connectors  = $channel->getConnectors()->map(
            function (Connector $connector) use ($registry, $channelType) {
                return $registry->getConnectorTypeBySettingEntity($connector, $channelType, true);
            }
        );

        $form = $this->get('form.factory')->create(
            ConnectorSelectType::NAME,
            null,
            [ConnectorSelectType::TYPE_OPTION => $channelType, 'already_used' => $connectors->toArray()]
        );

        return $form;
    }
}
