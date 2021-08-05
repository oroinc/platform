<?php

namespace Oro\Bundle\DataGridBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * REST API controller for grid views.
 */
class GridViewController extends RestController
{
    /**
     * @param Request $request
     *
     * @return Response
     *
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
    public function postAction(Request $request)
    {
        $this->checkCreateSharedAccess($request);

        return $this->handleCreateRequest();
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return Response
     *
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
    public function putAction(Request $request, $id)
    {
        /** @var AbstractGridView $gridView */
        $gridView = $this->getManager()->find($id);
        $this->checkSharedAccess($request, $gridView);

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
     * Set/unset grid view as default for current user.
     *
     * @param int  $id
     * @param bool $default
     * @param null $gridName
     *
     * @return Response
     * @ApiDoc(
     *      description="Set or unset grid view as default for current user",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="string"},
     *          {"name"="default", "dataType"="boolean"},
     *          {"name"="gridName", "dataType"="string"}
     *      },
     *     defaults={"default"="false"}
     * )
     * @Acl(
     *     id="oro_datagrid_gridview_view",
     *     type="entity",
     *     class="OroDataGridBundle:GridView",
     *     permission="VIEW"
     * )
     */
    public function defaultAction($id, $default = false, $gridName = null)
    {
        /** @var AbstractGridView $gridView */
        $manager  = $this->getManager();
        $gridView = $manager->getView($id, $default, $gridName);
        if ($gridView) {
            $manager->setDefaultGridView($this->getUser(), $gridView);
            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $gridView]);
    }

    /**
     * @throws AccessDeniedException
     */
    protected function checkSharedAccess(Request $request, AbstractGridView $view)
    {
        if ($request->request->get('type') !== $view->getType()) {
            if (!$this->isGridViewPublishGranted()) {
                throw new AccessDeniedException();
            }
        }
    }

    /**
     * @throws AccessDeniedException
     */
    protected function checkCreateSharedAccess(Request $request)
    {
        if ($request->request->get('type') !== AbstractGridView::TYPE_PUBLIC) {
            return;
        }

        if ($this->isGridViewPublishGranted()) {
            return;
        }

        throw new AccessDeniedException();
    }

    /**
     * @return bool
     */
    protected function isGridViewPublishGranted()
    {
        return $this->isGranted('oro_datagrid_gridview_publish');
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
