<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * REST API controller to update entity properties.
 */
class EntityDataController extends AbstractFOSRestController
{
    /**
     * Patch entity field/s data by new values
     *
     * @param int    $id
     * @param string $className
     *
     * @return Response
     *
     * @throws AccessDeniedException
     *
     * @ApiDoc(
     *      description="Update entity property",
     *      resource=true,
     *      requirements = {
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     */
    public function patchAction($className, $id)
    {
        $data = json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true);
        [$form, $data] = $this->getManager()->patch($className, $id, $data);

        if ($form->getErrors(true)->count() > 0) {
            $view = $this->view($form, Response::HTTP_BAD_REQUEST);
        } else {
            $statusCode = !empty($data) ? Response::HTTP_OK : Response::HTTP_NO_CONTENT;
            $view = $this->view($data, $statusCode);
        }

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_entity.manager.api.entity_data_api_manager');
    }
}
