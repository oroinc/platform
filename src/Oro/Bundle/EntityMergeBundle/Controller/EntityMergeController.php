<?php

namespace Oro\Bundle\EntityMergeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Route("/entity/merge")
 */
class EntityMergeController extends Controller
{
    /**
     * @Route(name="oro_entity_merge")
     * @Acl(
     *      id="oro_entity_merge",
     *      label="oro.entity_merge.action.merge",
     *      type="action"
     * )
     * @Template()
     */
    public function mergeAction()
    {
        return array();
    }

    /**
     * @Route("/test",name="oro_entity_merge_test")
     * @Acl(
     *      id="oro_entity_merge",
     *      label="oro.entity_merge.action.merge",
     *      type="action"
     * )
     * @Template()
     */
    public function testAction()
    {
        return array();
    }
}
