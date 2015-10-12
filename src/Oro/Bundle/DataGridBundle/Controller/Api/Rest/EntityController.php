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
use Oro\Bundle\SoapBundle\Handler\Context;

/**
 * @Rest\NamePrefix("oro_datagrid_api_rest_entity_")
 */
class EntityController extends FOSRestController
{
    const ACTION_PATCH = 'patch';

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

        if (!$this->getSecurityService()->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        if ($entity) {
            $form = $this->getManager()->update(
                $entity,
                json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true)
            );
            if ($entity) {
                $view = $this->view(null, Codes::HTTP_NO_CONTENT);
            } else {
                $view = $this->view($form, Codes::HTTP_BAD_REQUEST);
            }
        } else {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_PATCH, ['id' => $id, 'entity' => $entity]);

    }

    /**
     * @param mixed|View $data
     * @param string     $action
     * @param array      $contextValues
     * @param int        $status Used only if data was given in raw format
     *
     * @return Response
     */
    protected function buildResponse($data, $action, $contextValues = [], $status = Codes::HTTP_OK)
    {
        if ($data instanceof View) {
            $data->setFormat('json');
            $response = parent::handleView($data);
        } else {
            $headers = isset($contextValues['headers']) ? $contextValues['headers'] : [];
            unset($contextValues['headers']);

            $response = new JsonResponse($data, $status, $headers);
        }

        $includeHandler = $this->get('oro_soap.handler.include');
        $includeHandler->handle(new Context($this, $this->get('request'), $response, $action, $contextValues));

        return $response;
    }
}
