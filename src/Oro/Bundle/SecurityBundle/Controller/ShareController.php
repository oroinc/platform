<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\Form\Model\Share;

/**
 * @Route("/share")
 */
class ShareController extends Controller
{
    /**
     * @Route("/update/{entityClass}/{entityId}", name="oro_share_update")
     * @Template("OroSecurityBundle:Share:update.html.twig")
     */
    public function updateAction($entityClass, $entityId)
    {
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $entity = $entityRoutingHelper->getEntity($entityClass, $entityId);
        if (!$this->get('oro_security.security_facade')->isGranted('SHARE', $entity)) {
            throw new AccessDeniedException();
        }

        $formAction = $entityRoutingHelper->generateUrlByRequest(
            'oro_share_update',
            $this->getRequest(),
            $entityRoutingHelper->getRouteParameters($entityClass, $entityId)
        );

        return $this->update($this->get('oro_security.form.model.factory')->getShare(), $entity, $formAction);
    }

    /**
     * @param Share $model
     * @param object $entity
     * @param string $formAction
     *
     * @return array
     */
    protected function update(Share $model, $entity, $formAction)
    {
        $responseData = [
            'entity' => $entity,
            'model' => $model,
            'saved' => false
        ];

        if ($this->get('oro_security.form.handler.share')->process($model, $entity)) {
            $responseData['saved'] = true;
        }
        $responseData['form'] = $this->get('oro_security.form.share')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
    }
}
