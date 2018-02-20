<?php

namespace Oro\Bundle\DashboardBundle\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\RouteResource("dashboard_widget")
 * @Rest\NamePrefix("oro_api_")
 */
class WidgetController extends FOSRestController implements ClassResourceInterface
{
    /**
     * @param Request $request
     * @param integer $dashboardId
     * @param integer $widgetId
     *
     * @Rest\QueryParam(
     *      name="isExpanded",
     *      requirements="(1)|(0)",
     *      nullable=true,
     *      strict=true,
     *      description="Set collapse or expand"
     * )
     * @Rest\QueryParam(
     *      name="layoutPosition",
     *      nullable=true,
     *      strict=true,
     *      description="Set layout position"
     * )
     *
     * @ApiDoc(
     *      description="Update dashboard widget",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_dashboard_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroDashboardBundle:Dashboard"
     * )
     * @return Response
     */
    public function putAction(Request $request, $dashboardId, $widgetId)
    {
        $dashboard = $this->getDashboardManager()->findDashboardModel($dashboardId);
        $widget = $this->getDashboardManager()->findWidgetModel($widgetId);

        if (!$dashboard || !$widget) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        if (!$dashboard->hasWidget($widget)) {
            return $this->handleView($this->view(array(), Codes::HTTP_BAD_REQUEST));
        }

        $widget->setExpanded(
            $request->get('isExpanded', $widget->isExpanded())
        );

        $widget->setLayoutPosition(
            $request->get('layoutPosition', $widget->getLayoutPosition())
        );

        $this->getEntityManager()->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @param integer $dashboardId
     * @param integer $widgetId
     *
     * @ApiDoc(
     *      description="Delete dashboard widget",
     *      resource=true
     * )
     * @AclAncestor("oro_dashboard_update")
     * @return Response
     */
    public function deleteAction($dashboardId, $widgetId)
    {
        $dashboard = $this->getDashboardManager()->findDashboardModel($dashboardId);
        $widget = $this->getDashboardManager()->findWidgetModel($widgetId);

        if (!$dashboard || !$widget) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        if (!$dashboard->hasWidget($widget)) {
            return $this->handleView($this->view(array(), Codes::HTTP_BAD_REQUEST));
        }

        $this->getDashboardManager()->remove($widget);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @param Request $request
     * @param integer $dashboardId
     *
     * @Rest\QueryParam(
     *      name="layoutPositions",
     *      nullable=true,
     *      strict=true,
     *      description="Array of layout positions"
     * )
     *
     * @ApiDoc(
     *      description="Update dashboard widgets positions",
     *      resource=true
     * )
     * @AclAncestor("oro_dashboard_update")
     *
     * @return Response
     */
    public function putPositionsAction(Request $request, $dashboardId)
    {
        $dashboard = $this->getDashboardManager()->findDashboardModel($dashboardId);

        if (!$dashboard) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $layoutPositions = $request->get('layoutPositions', []);

        foreach ($layoutPositions as $widgetId => $layoutPosition) {
            if ($widget = $this->getDashboardManager()->findWidgetModel($widgetId)) {
                $widget->setLayoutPosition($layoutPosition);
            }
        }

        $this->getEntityManager()->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @Rest\QueryParam(
     *      name="dashboardId",
     *      nullable=false,
     *      strict=true,
     *      description="Dashboard id"
     * )
     * @Rest\QueryParam(
     *      name="widgetName",
     *      nullable=false,
     *      strict=true,
     *      description="Dashboard widget name"
     * )
     * @ApiDoc(
     *      description="Add widget to dashboard",
     *      resource=true
     * )
     * @AclAncestor("oro_dashboard_update")
     * @param Request $request
     * @return Response
     */
    public function postAddWidgetAction(Request $request)
    {
        $dashboardId = $request->get('dashboardId');
        $widgetName = $request->get('widgetName');
        $targetColumn = (int)$request->get('targetColumn', 0);

        $dashboard = $this->getDashboardManager()->findDashboardModel($dashboardId);

        if (!$dashboard || !$widgetName) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $widget = $this->getDashboardManager()->createWidgetModel($widgetName);
        $dashboard->addWidget($widget, $targetColumn);
        $this->getDashboardManager()->save($widget, true);

        return $this->handleView($this->view($widget, Codes::HTTP_OK));
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @return Manager
     */
    protected function getDashboardManager()
    {
        return $this->get('oro_dashboard.manager');
    }
}
