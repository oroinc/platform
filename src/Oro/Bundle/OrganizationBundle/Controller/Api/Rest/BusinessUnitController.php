<?php

namespace Oro\Bundle\OrganizationBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for BusinessUnit entity.
 */
class BusinessUnitController extends RestController
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all business units items",
     *      resource=true
     * )
     * @param Request $request
     * @return Response
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. defaults to 10.',
        nullable: true
    )]
    #[AclAncestor('oro_business_unit_view')]
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
     */
    #[AclAncestor('oro_business_unit_create')]
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
     * @return Response
     */
    #[AclAncestor('oro_business_unit_update')]
    public function putAction(int $id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * REST GET item
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Get business unit item",
     *      resource=true
     * )
     * @return Response
     */
    #[AclAncestor('oro_business_unit_view')]
    public function getAction(int $id)
    {
        return $this->handleGetRequest($id);
    }

    #[\Override]
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

    #[\Override]
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
     * @return Response
     */
    #[Acl(id: 'oro_business_unit_delete', type: 'entity', class: BusinessUnit::class, permission: 'DELETE')]
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_organization.business_unit.manager.api');
    }

    #[\Override]
    public function getForm()
    {
        return $this->container->get('oro_organization.form.business_unit.api');
    }

    #[\Override]
    public function getFormHandler()
    {
        return $this->container->get('oro_organization.form.handler.business_unit.api');
    }
}
