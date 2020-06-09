<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Updates the database and all related caches to reflect changes made in extended entities.
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
        /** @var EntityExtendUpdateHandlerInterface $entityExtendUpdateHandler */
        $entityExtendUpdateHandler = $this->get('oro_entity_extend.extend.update_handler');

        $result = $entityExtendUpdateHandler->update();
        if ($result->isSuccessful()) {
            return new JsonResponse();
        }

        $responseData = [];
        $failedMessage = $result->getFailedMessage();
        if ($failedMessage) {
            $responseData['message'] = $failedMessage;
        }

        return new JsonResponse($responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
