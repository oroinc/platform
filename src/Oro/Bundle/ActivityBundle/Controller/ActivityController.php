<?php

namespace Oro\Bundle\ActivityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/activities")
 */
class ActivityController extends Controller
{
    /**
     * @param object $entity The entity object which activities should be rendered
     *
     * @return Response
     *
     * @Route(
     *      "/view/{entity}",
     *      name="oro_activity_view_activities"
     * )
     */
    public function activitiesAction($entity)
    {
        $widgetProvider = $this->get('oro_activity.widget_provider');

        $widgets = $widgetProvider->supports($entity)
            ? $widgetProvider->getWidgets($entity)
            : [];

        if (empty($widgets)) {
            // return empty response to prevent rendering 'Activities' placeholder
            return new Response();
        }

        return $this->render('OroActivityBundle:Activity:activities.html.twig', ['tabs' => $widgets]);
    }
}
