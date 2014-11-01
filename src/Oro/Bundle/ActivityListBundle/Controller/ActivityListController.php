<?php

namespace Oro\Bundle\ActivityListBundle\Controller;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/activity-list")
 */
class ActivityListController extends Controller
{
    /**
     * @Route(
     *      "/view/widget/{entityClass}/{entityId}",
     *      name="oro_activity_list_widget_activities"
     * )
     *
     * @ AclAncestor("oro_activity_list_view")
     * @Template("OroActivityListBundle:ActivityList:activities.html.twig")
     *
     * @param string  $entityClass The entity class which activities should be rendered
     * @param integer $entityId    The entity object id which activities should be rendered
     *
     * @return array
     */
    public function widgetAction($entityClass, $entityId)
    {
        $entity = $this->getEntityRoutingHelper()->getEntity($entityClass, $entityId);

        return [
            'entity' => $entity
        ];
    }


    /**
     * @Route(
     *      "/view/{entity}",
     *      name="oro_activity_view_activities"
     * )
     *
     * @param object $entity The entity object which activities should be rendered
     *
     * @return Response
     */
    public function activitiesAction($entity)
    {
//        $widgetProvider = $this->get('oro_activity.widget_provider.activities');
//        $widgets = $widgetProvider->supports($entity)
//            ? $widgetProvider->getWidgets($entity)
//            : [];
//
//        if (empty($widgets)) {
//            // return empty response to prevent rendering 'Activities' placeholder
//            return new Response();
//        }

        return $this->render(
            'OroActivityListBundle:ActivityList:activities.html.twig',
            [
                'entity' => $entity
            ]
        );
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->get('oro_entity.routing_helper');
    }
}
