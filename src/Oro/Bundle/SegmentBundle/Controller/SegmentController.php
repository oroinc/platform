<?php

namespace Oro\Bundle\SegmentBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class SegmentController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_segment_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     *
     * @Template
     * @AclAncestor("oro_segment_view")
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/view/{id}", name="oro_segment_view", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Acl(
     *      id="oro_segment_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroSegmentBundle:Segment"
     * )
     */
    public function viewAction(Segment $entity)
    {

    }

    /**
     * @Route("/create", name="oro_segment_create")
     * @Template("OroSegmentBundle:Segment:update.html.twig")
     * @Acl(
     *      id="oro_segment_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroSegmentBundle:Segment"
     * )
     */
    public function createAction()
    {
        return $this->update(new Segment());
    }

    /**
     * @Route("/update/{id}", name="oro_segment_update", requirements={"id"="\d+"}, defaults={"id"=0})
     *
     * @Template
     * @Acl(
     *      id="oro_segment_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroSegmentBundle:Segment"
     * )
     */
    public function updateAction(Segment $entity)
    {
        return $this->update($entity);
    }

    /**
     * @param Segment $entity
     *
     * @return array
     */
    protected function update(Segment $entity)
    {
        return $entity;
    }
}
