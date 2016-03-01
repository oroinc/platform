<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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

        if (!$entityProcessor->updateDatabase(true, true)) {
            throw new HttpException(500, 'Update failed');
        }

        return new Response();
    }
}
