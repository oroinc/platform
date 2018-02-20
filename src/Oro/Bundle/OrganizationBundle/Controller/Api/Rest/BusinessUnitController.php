<?php

namespace Oro\Bundle\OrganizationBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("businessunit")
 * @NamePrefix("oro_api_")
 */
class BusinessUnitController extends RestController implements ClassResourceInterface
{
    /**
     * REST GET list
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. defaults to 10."
     * )
     * @ApiDoc(
     *      description="Get all business units items",
     *      resource=true,
     *      filters={
     *          {"name"="page", "dataType"="integer"},
     *          {"name"="limit", "dataType"="integer"}
     *      }
     * )
     * @AclAncestor("oro_business_unit_view")
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * Create new business unit
     *
     * @ApiDoc(
     *      description="Create new business unit",
     *      resource=true
     * )
     * @AclAncestor("oro_business_unit_create")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * REST PUT
     *
     * @param int $id Business unit item id
     *
     * @ApiDoc(
     *      description="Update business unit",
     *      resource=true
     * )
     * @AclAncestor("oro_business_unit_update")
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * REST GET item
     *
     * @param string $id
     *
     * @ApiDoc(
     *      description="Get business unit item",
     *      resource=true
     * )
     * @AclAncestor("oro_business_unit_view")
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function transformEntityField($field, &$value)
    {
        switch ($field) {
            case 'organization':
                if ($value) {
                    /** @var Organization $value */
                    $value = array(
                        'id' => $value->getId(),
                        'name' => $value->getName(),
                    );
                }
                break;
            case 'owner':
                if ($value) {
                    $value = array(
                        'id' => $value->getId(),
                        'name' => $value->getName()
                    );
                }
                break;
            default:
                parent::transformEntityField($field, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        $result = parent::getPreparedItem($entity);

        unset($result['users']);

        return $result;
    }

    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete business unit",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_business_unit_delete",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="DELETE"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_organization.business_unit.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->get('oro_organization.form.business_unit.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_organization.form.handler.business_unit.api');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeleteHandler()
    {
        return $this->get('oro_organization.business_unit.handler.delete');
    }
}
