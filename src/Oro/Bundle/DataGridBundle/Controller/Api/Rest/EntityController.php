<?php

namespace Oro\Bundle\DataGridBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\EntityBundle\Exception\EntityHasFieldException;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @Rest\NamePrefix("oro_datagrid_api_rest_entity_")
 */
class EntityController extends FOSRestController
{
    /**
     * @param int $id
     * @param int $className
     *
     * @return Response
     *
     * @Rest\Patch("entity/{className}/{id}")
     * @ApiDoc(
     *      description="Update entity property",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     */
    public function patchAction($className, $id)
    {
        try {
            $entity = $this->get('oro_entity.routing_helper')->getEntity($className, $id);
        } catch (\Exception $e) {
            return parent::handleView($this->view(['message'=>$e->getMessage()], Codes::HTTP_NOT_FOUND));
        }

        if (!$this->getSecurityService()->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        try {
            $form = $this->getManager()->update(
                $entity,
                json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true)
            );

            if ($form->getErrors()->count() > 0) {
                $view = $this->view($form, Codes::HTTP_BAD_REQUEST);
            } else {
                $view = $this->view($form, Codes::HTTP_NO_CONTENT);
            }
        } catch (FieldUpdateAccessException $e) {
            throw new AccessDeniedException("You does not have access to edit this field name");
        } catch (EntityHasFieldException $e) {
            $view = $this->view(['message'=> 'Field Name is not founded in entity'], Codes::HTTP_NOT_FOUND);
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
