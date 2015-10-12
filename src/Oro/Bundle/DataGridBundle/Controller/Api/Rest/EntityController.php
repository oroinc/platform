<?php

namespace Oro\Bundle\DataGridBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @Rest\NamePrefix("oro_datagrid_api_rest_entity_")
 */
class EntityController extends FOSRestController
{
    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_datagrid.extension.inline_editing.entity.manager');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityService()
    {
        return $this->get('security.authorization_checker');
    }

    /**
     * @param int $id
     *
     * @return Response
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
        $entity = $this->get('oro_entity.routing_helper')->getEntity($className, $id);
//        $className = strtr($className, '', '\\');
//        $entity = $this->getManager()->getEntity($className, $id);

        if (!$this->getSecurityService()->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $this->getManager()->updateFields(
            $entity,
            json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true)
        );
        $response = ['status' => true];

        return new JsonResponse($response);
    }
}
