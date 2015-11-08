<?php

namespace Oro\Bundle\ActivityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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
     * @Route("/{activity}/{id}/context", name="oro_activity_context")
     *
     * @Template("OroActivityBundle:Activity/dialog:context.html.twig")
     *
     * @param string $activity
     * @param string $id
     *
     * @return array
     *
     * @throws AccessDeniedException
     */
    public function contextAction($activity, $id)
    {
        $routingHelper = $this->get('oro_entity.routing_helper');
        $entity        = $routingHelper->getEntity($activity, $id);
        $entityClass   = $routingHelper->resolveEntityClass($activity);

        if (!$this->isGranted('VIEW', $entity)) {
            throw new AccessDeniedException();
        }

        $entityTargets    = $this->get('oro_entity.entity_context_provider')->getSupportedTargets($entity);
        $entityClassAlias = $this->get('oro_entity.entity_alias_resolver')->getPluralAlias($entityClass);

        return [
            'sourceEntity'           => $entity,
            'sourceEntityClassAlias' => $entityClassAlias,
            'entityTargets'          => $entityTargets,
            'params'                 => [
                'grid_path' => $this->generateUrl(
                    'oro_activity_context_grid',
                    ['activity' => $activity, 'id' => $id],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ]
        ];
    }

    /**
     * @Route("/{activity}/{id}/context/grid/{entityClass}", name="oro_activity_context_grid")
     *
     * @Template("OroDataGridBundle:Grid:dialog/widget.html.twig")
     *
     * @param string $entityClass
     * @param string $activity
     * @param string $id
     *
     * @return array
     */
    public function contextGridAction($activity, $id, $entityClass = null)
    {
        $gridName = $this->get('oro_entity.entity_context_provider')->getContextGridByEntity($entityClass);

        // Need to specify parameters for Oro\Bundle\ActivityBundle\EventListener\Datagrid\ContextGridListener
        $params = [
            'activityClass' => $activity,
            'activityId'    => $id
        ];

        return [
            'gridName'     => $gridName,
            'multiselect'  => false,
            'params'       => $params,
            'renderParams' => []
        ];
    }
}
