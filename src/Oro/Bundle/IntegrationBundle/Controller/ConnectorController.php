<?php

namespace Oro\Bundle\IntegrationBundle\Controller;

use Oro\Bundle\IntegrationBundle\Entity\Connector;
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
                    'id'   => $connector->getId(),
                    'type' => $registry->getConnectorTypeBySettingEntity($connector, $channelType)->getLabel()
                ];
            }
        );

        return [
            'connectors' => $connectors
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
     * Return "select connector type" form
     *
     * @param string $channelType
     *
     * @return FormInterface
     */
    protected function getForm($channelType)
    {
        $form = $this->get('form.factory')->create(
            TransportSelectType::NAME,
            null,
            [TransportSelectType::TYPE_OPTION => $channelType]
        );

        return $form;
    }
}
