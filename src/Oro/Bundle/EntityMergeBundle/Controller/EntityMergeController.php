<?php

namespace Oro\Bundle\EntityMergeBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionParametersParser;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Route("/merge")
 */
class EntityMergeController extends Controller
{
    /**
     * @Route("/{gridName}/massAction/{actionName}", name="oro_entity_merge")
     * @Acl(
     *      id="oro_entity_merge",
     *      label="oro.entity_merge.action.merge",
     *      type="action"
     * )
     * @param string $gridName
     * @param string $actionName
     * @return array
     * @Template("OroEntityMergeBundle:Merge:merge.html.twig")
     */
    public function mergeAction($gridName, $actionName)
    {



        return array('result' => '');
    }


}
