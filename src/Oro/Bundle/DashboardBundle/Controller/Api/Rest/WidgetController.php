<?php

namespace Oro\Bundle\DashboardBundle\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Rest\RouteResource("dashboard_widget")
 * @Rest\NamePrefix("oro_api_")
 */
class WidgetController extends FOSRestController implements ClassResourceInterface
{
    /**
     * @param integer $id
     *
     * @ApiDoc(
     *      description="Delete dashboard widget",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_dashboard_widget",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroDashboardBundle:DashboardWidget"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        $widget = $this->getWidget($id);

        if (!$widget) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $this->getEntityManager()->remove($widget);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @param integer $id
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
     *      id="oro_dashboard_widget",
     *      type="entity",
     *      permission="UPDATE",
     *      class="OroDashboardBundle:DashboardWidget"
     * )
     * @return Response
     */
    public function putAction($id)
    {
        $widget = $this->getWidget($id);

        if (!$widget) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }

        $widget->setExpanded(
            $this->getRequest()->get('isExpanded', $widget->isExpanded())
        );

        $widget->setLayoutPosition(
            $this->getRequest()->get('layoutPosition', $widget->getLayoutPosition())
        );

        $this->getEntityManager()->flush($widget);

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @Rest\Put()
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
     * @Acl(
     *      id="oro_dashboard_widget",
     *      type="entity",
     *      permission="UPDATE",
     *      class="OroDashboardBundle:DashboardWidget"
     * )
     * @return Response
     */
    public function positionsAction()
    {
        $layoutPositions = $this->getRequest()->get('layoutPositions', []);
        $widgets         = [];

        foreach ($layoutPositions as $widgetId => $layoutPosition) {
            if ($widget = $this->getWidget($widgetId)) {
                $widget->setLayoutPosition($layoutPosition);

                $widgets[] = $widget;
            }
        }

        $this->getEntityManager()->flush($widgets);

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @param integer $id
     * @return \Oro\Bundle\DashboardBundle\Entity\DashboardWidget
     */
    protected function getWidget($id)
    {
        $entity = $this
            ->getEntityManager()
            ->getRepository('OroDashboardBundle:DashboardWidget')
            ->find($id);

        return $entity;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }
}
