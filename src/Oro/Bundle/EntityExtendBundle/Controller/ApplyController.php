<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;

/**
 * EntityExtendBundle controller.
 * @Route("/entity/extend")
 * TODO: Discuss ACL impl., currently acl is disabled
 */
class ApplyController extends Controller
{
    /**
     * @Route(
     *      "/update/{id}",
     *      name="oro_entityextend_update",
     *      defaults={"id"=0}
     * )
     * Acl(
     *      id="oro_entityextend_update",
     *      label="oro.entity_extend.action.apply_changes",
     *      type="action",
     *      group_name=""
     * )
     */
    public function updateAction()
    {
        /** @var EntityProcessor $entityProcessor */
        $entityProcessor = $this->get('oro_entity_extend.extend.entity_processor');

        $flashBag = $this->get('session')->getFlashBag();
        if ($entityProcessor->updateDatabase()) {
            $flashBag->add(
                'success',
                $this->get('translator')->trans('oro.entity_config.controller.config_entity.message.update')
            );
        }

        return $this->redirect($this->generateUrl('oro_entityconfig_index'));
    }
}
