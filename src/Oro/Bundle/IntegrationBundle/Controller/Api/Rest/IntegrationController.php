<?php

namespace Oro\Bundle\IntegrationBundle\Controller\Api\Rest;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
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
     * Activate integration
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/integrations/{id}/activate",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Activate integration", resource=true)
     * @Acl(
     *      id="oro_integration_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroIntegrationBundle:Channel"
     * )
     *
     * @return Response
     */
    public function activateAction($id)
    {
        /** @var Channel $integration */
        $integration = $this->getManager()->find($id);

        if (!EditModeUtils::isSwitchEnableAllowed($integration->getEditMode())) {
            return $this->handleView($this->view(null, Codes::HTTP_BAD_REQUEST));
        }
        $integration->setPreviouslyEnabled($integration->isEnabled());
        $integration->setEnabled(true);

        $objectManager = $this->getManager()->getObjectManager();
        $objectManager->persist($integration);
        $objectManager->flush();
        
        $this->getSyncScheduler()->schedule($integration);

        return $this->handleView(
            $this->view(
                [
                    'message'    => $this->get('translator')->trans('oro.integration.notification.channel.activated'),
                    'success' => true,
                ],
                Codes::HTTP_OK
            )
        );
    }

    /**
     * Deactivate integration
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/integrations/{id}/deactivate",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Deactivate integration", resource=true)
     * @Acl(
     *      id="oro_integration_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroIntegrationBundle:Channel"
     * )
     *
     * @return Response
     */
    public function deactivateAction($id)
    {
        /** @var Channel $integration */
        $integration = $this->getManager()->find($id);
        if (!EditModeUtils::isSwitchEnableAllowed($integration->getEditMode())) {
            return $this->handleView($this->view(null, Codes::HTTP_BAD_REQUEST));
        }
        $integration->setPreviouslyEnabled($integration->isEnabled());
        $integration->setEnabled(false);

        $objectManager = $this->getManager()->getObjectManager();
        $objectManager->persist($integration);
        $objectManager->flush();

        return $this->handleView(
            $this->view(
                [
                    'message'    => $this->get('translator')->trans('oro.integration.notification.channel.deactivated'),
                    'success' => true,
                ],
                Codes::HTTP_OK
            )
        );
    }

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

        if (!EditModeUtils::isEditAllowed($entity->getEditMode())) {
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

    /**
     * @return GenuineSyncScheduler
     */
    protected function getSyncScheduler()
    {
        return $this->get('oro_integration.genuine_sync_scheduler');
    }
}
