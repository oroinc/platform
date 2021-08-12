<?php

namespace Oro\Bundle\DashboardBundle\Controller\Api\Rest;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for dashboard widgets.
 */
class WidgetController extends AbstractFOSRestController
{
    /**
     * @param Request $request
     * @param int $dashboardId
     * @param int $widgetId
     *
     * @QueryParam(
     *      name="isExpanded",
     *      requirements="(1)|(0)",
     *      nullable=true,
     *      strict=true,
     *      description="Set collapse or expand"
     * )
     * @QueryParam(
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
            return $this->handleNotFound();
        }

        if (!$dashboard->hasWidget($widget)) {
            return $this->handleBadRequest();
        }

        $widget->setExpanded(
            $request->get('isExpanded', $widget->isExpanded())
        );

        $widget->setLayoutPosition(
            $request->get('layoutPosition', $widget->getLayoutPosition())
        );

        $this->getEntityManager()->flush();

        return $this->handleNoContent();
    }

    /**
     * @param int $dashboardId
     * @param int $widgetId
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
            return $this->handleNotFound();
        }

        if (!$dashboard->hasWidget($widget)) {
            return $this->handleBadRequest();
        }

        $this->getDashboardManager()->remove($widget);
        $this->getEntityManager()->flush();

        return $this->handleNoContent();
    }

    /**
     * @param Request $request
     * @param int $dashboardId
     *
     * @QueryParam(
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
            return $this->handleNotFound();
        }

        $layoutPositions = $request->get('layoutPositions', []);

        foreach ($layoutPositions as $widgetId => $layoutPosition) {
            $widget = $this->getDashboardManager()->findWidgetModel($widgetId);
            if ($widget) {
                $widget->setLayoutPosition($layoutPosition);
            }
        }

        $this->getEntityManager()->flush();

        return $this->handleNoContent();
    }

    /**
     * @QueryParam(
     *      name="dashboardId",
     *      nullable=false,
     *      strict=true,
     *      description="Dashboard id"
     * )
     * @QueryParam(
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
            return $this->handleNotFound();
        }

        $widget = $this->getDashboardManager()->createWidgetModel($widgetName);
        $dashboard->addWidget($widget, $targetColumn);
        $this->getDashboardManager()->save($widget, true);

        $responseData = [
            'id' => $widget->getId(),
            'name' => $widget->getName(),
            'config' => $widget->getConfig(),
            'layout_position' => $widget->getLayoutPosition(),
            'expanded' => $widget->isExpanded()
        ];

        return $this->handleView($this->view($responseData, Response::HTTP_OK));
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getManager();
    }

    private function getDashboardManager(): Manager
    {
        return $this->get('oro_dashboard.manager');
    }

    private function handleNotFound(): Response
    {
        return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
    }

    private function handleBadRequest(): Response
    {
        return $this->handleView($this->view([], Response::HTTP_BAD_REQUEST));
    }

    private function handleNoContent(): Response
    {
        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }
}
