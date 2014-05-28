<?php

namespace Oro\Bundle\NoteBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;

/**
 * @RouteResource("note")
 * @NamePrefix("oro_api_")
 */
class NoteController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get note entities.
     *
     * @ApiDoc(
     *      description="Get note entities",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction()
    {
    }


}
