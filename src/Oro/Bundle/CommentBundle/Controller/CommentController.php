<?php

namespace Oro\Bundle\CommentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

/**
 * @Route("/comments")
 */
class CommentController extends Controller
{

    /**
     * @Route(
     *      "/view/widget/{entityClass}/{entityId}",
     *      name="oro_comment_widget_comments"
     * )
     *
     * @AclAncestor("oro_comment_view")
     * @Template("OroCommentBundle:Comment:comments.html.twig")
     */
    public function widgetAction($entityClass, $entityId)
    {
        $entity = $this->getEntityRoutingHelper()->getEntity($entityClass, $entityId);

        return [
            'entity' => $entity
        ];
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->get('oro_entity.routing_helper');
    }
}
