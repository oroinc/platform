<?php

namespace Oro\Bundle\DataGridBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;

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
        $entity = $this->get('oro_entity.routing_helper')->getEntity($className, $id);

        if (!$entity) {
            return parent::handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }
        if (!$this->getSecurityService()->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $form = $this->getManager()->update(
            $entity,
            json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true)
        );
        if ($form->getErrors()->count() > 0) {
            $view = $this->view($form, Codes::HTTP_BAD_REQUEST);
        } else {
            $view = $this->view($form, Codes::HTTP_NO_CONTENT);
        }
        $response = parent::handleView($view);

        return $response;
    }
}
