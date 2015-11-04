<?php

namespace Oro\Bundle\ActivityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
        $widgetProvider = $this->get('oro_activity.widget_provider.activities');

        $widgets = $widgetProvider->supports($entity)
            ? $widgetProvider->getWidgets($entity)
            : [];

        if (empty($widgets)) {
            // return empty response to prevent rendering 'Activities' placeholder
            return new Response();
        }

        return $this->render('OroActivityBundle:Activity:activities.html.twig', ['tabs' => $widgets]);
    }

    /**
     * @Route("/context/{entityClass}/{entityId}", name="oro_activity_context")
     * @Template("OroActivityBundle:Activity:context.html.twig")
     *
     * @param string $entityClass
     * @param string $entityId
     *
     * @return array
     *
     * @throws AccessDeniedException
     */
    public function contextAction($entityClass, $entityId)
    {
        $entity = $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId);
        if (!$this->get('oro_security.security_facade')->isGranted('VIEW', $entity)) {
            throw new AccessDeniedException();
        }

        $entityTargets = $this->get('oro_entity.entity_context_provider')->getSupportedTargets($entity);
        return [
            'sourceEntity' => $entity,
            'entityTargets' => $entityTargets,
            'params' => [
                'grid_path' => $this->generateUrl(
                    'oro_activity_context_grid',
                    ['activityId' => $entity->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ]
        ];
    }

    /**
     * @Route("/context/grid/{activityId}/{entityClass}", name="oro_activity_context_grid")
     * @Template("OroDataGridBundle:Grid:dialog/widget.html.twig")
     *
     * @param string $entityClass
     * @param string $activityId
     *
     * @return array
     */
    public function contextGridAction($activityId, $entityClass = null)
    {
        $gridName = $this->get('oro_entity.entity_context_provider')->getContextGridByEntity($entityClass);
        return [
            'gridName' => $gridName,
            'multiselect' => false,
            'params' => ['activityId' => $activityId],
            'renderParams' => []
        ];
    }
}
