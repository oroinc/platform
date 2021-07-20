<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateHandlerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Updates the database schema and all related caches to reflect changes made in extended entities.
 *
 * @Route("/entity/extend")
 */
class ApplyController
{
    /** @var EntityExtendUpdateHandlerInterface */
    private $entityExtendUpdateHandler;

    public function __construct(EntityExtendUpdateHandlerInterface $entityExtendUpdateHandler)
    {
        $this->entityExtendUpdateHandler = $entityExtendUpdateHandler;
    }

    /**
     * @Route(
     *      "/update/{id}",
     *      name="oro_entityextend_update",
     *      defaults={"id"=0}
     * )
     */
    public function updateAction(): JsonResponse
    {
        $result = $this->entityExtendUpdateHandler->update();
        if ($result->isSuccessful()) {
            return new JsonResponse();
        }

        $responseData = [];
        $failureMessage = $result->getFailureMessage();
        if ($failureMessage) {
            $responseData['message'] = $failureMessage;
        }

        return new JsonResponse($responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
