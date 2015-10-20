<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\EntityBundle\Exception\EntityHasFieldException;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @RouteResource("entity_data")
 * @NamePrefix("oro_api_")
 */
class EntityDataController extends FOSRestController
{
    /**
     * Patch entity field/s data by new values
     *
     * @param int $id
     * @param int $className
     *
     * @return Response
     *
     * @throws AccessDeniedException
     *
     * @Rest\Patch("entity/{className}/{id}")
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
        try {
            $entity = $this->get('oro_entity.routing_helper')->getEntity($className, $id);
        } catch (\Exception $e) {
            return parent::handleView($this->view(['message' => $e->getMessage()], Codes::HTTP_NOT_FOUND));
        }
        if (!$this->getSecurityService()->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }
        try {
            $result = $this->getManager()->update(
                $entity,
                json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true)
            );

            $form = $result['form'];
            $changeSet = $result['changeSet'];

            if ($form->getErrors()->count() > 0) {
                $view = $this->view($form, Codes::HTTP_BAD_REQUEST);
            } else {
                $view = $this->view($changeSet, Codes::HTTP_OK);
            }
        } catch (FieldUpdateAccessException $e) {
            throw new AccessDeniedException('oro.entity.controller.message.access_denied');
        } catch (EntityHasFieldException $e) {
            $view = $this->view(['message' => 'oro.entity.controller.message.field_not_found'], Codes::HTTP_NOT_FOUND);
        }

        $response = parent::handleView($view);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_entity.manager.entity_field_manager');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityService()
    {
        return $this->get('security.authorization_checker');
    }
}
