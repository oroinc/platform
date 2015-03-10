<?php

namespace Oro\Bundle\DataGridBundle\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\RouteResource("datagrid_gridview")
 * @Rest\NamePrefix("oro_api_")
 */
class GridViewController extends RestController
{
    /**
     * @param int $id
     * 
     * @return Response
     * @ApiDoc(
     *      description="Create grid view",
     *      resource=true,
     * )
     * @Acl(
     *     id="oro_datagrid_gridview_create",
     *     type="entity",
     *     class="OroDataGridBundle:GridView",
     *     permission="CREATE"
     * )
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * @param int $id
     * 
     * @return Response
     * @ApiDoc(
     *      description="Update grid view",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @Acl(
     *     id="oro_datagrid_gridview_update",
     *     type="entity",
     *     class="OroDataGridBundle:GridView",
     *     permission="EDIT"
     * )
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * @param int $id
     * 
     * @return Response
     * @ApiDoc(
     *      description="Delete grid view",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @Acl(
     *     id="oro_datagrid_gridview_delete",
     *     type="entity",
     *     class="OroDataGridBundle:GridView",
     *     permission="DELETE"
     * )
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity()
    {
        $entity = parent::createEntity();
        $entity->setOwner($this->getUser());

        return $entity;
    }

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
        return $this->get('oro_datagrid.grid_view.manager.api');
    }
}
