<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Oro\Bundle\EntityExtendBundle\Extend\EntityProcessor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Apply entity config controller.
 *
 * @Route("/entity/extend")
 */
class ApplyController extends Controller
{
    /**
     * @Route(
     *      "/update/{id}",
     *      name="oro_entityextend_update",
     *      defaults={"id"=0}
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
