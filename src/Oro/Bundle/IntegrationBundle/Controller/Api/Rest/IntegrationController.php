<?php

namespace Oro\Bundle\IntegrationBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

/**
 * @RouteResource("integration")
 * @NamePrefix("oro_api_")
 */
class IntegrationController extends FOSRestController
{
    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete Integration",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_integration_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroIntegrationBundle:Channel"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        /** @var Integration $entity */
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        if ($entity->getEditMode() === $entity::EDIT_MODE_DISALLOW) {
            return $this->handleView($this->view(null, Codes::HTTP_BAD_REQUEST));
        }

        $result = $this->get('oro_integration.delete_manager')->delete($entity);
        if (!$result) {
            return $this->handleView($this->view(null, Codes::HTTP_INTERNAL_SERVER_ERROR));
        }

        return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_integration.manager.api');
    }
}
