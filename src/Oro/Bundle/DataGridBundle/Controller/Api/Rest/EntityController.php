<?php

namespace Oro\Bundle\DataGridBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @Rest\NamePrefix("oro_datagrid_api_rest_entity_")
 *
 */
class EntityController extends RestController
{
    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->get('oro_datagrid.form.grid_view.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_datagrid.grid_view.form.handler.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_datagrid.entity.manager.api');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->get('oro_security.security_facade');
    }

    /**
     * @param int $id
     *
     * @return Response
     * @Rest\Patch("entity/{className}/{entityId}", requirements={"id"="\d+"})
     * @ApiDoc(
     *      description="Update entity property",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     */
    public function patchAction($className, $entityId)
    {
        $className = strtr($className, '-', '\\');
        $entity = $this->getManager()->getEntity($className, $entityId);

        if ($this->get('security.authorization_checker')->isGranted('EDIT', $entity)) {
            $request = $this->get('request_stack')->getCurrentRequest();
            $content = $request->getContent();
            $content = json_decode($content, true);
            foreach ($content as $fieldName => $fieldValue) {
                $this->getManager()->updateField($entity, $fieldName, $fieldValue);
            }

            $response = ['status' => true];
        } else {
            $response = ['status' => false];
        }

        return new JsonResponse($response);
    }
}
