<?php

namespace Oro\Bundle\SegmentBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Grid\ConfigurationProvider;

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
     * @Template
     */
    public function viewAction(Segment $entity)
    {
        $segmentGroup = $this->get('oro_entity_config.provider.entity')
            ->getConfig($entity->getEntity())
            ->get('plural_label');

        $gridName = ConfigurationProvider::GRID_PREFIX . $entity->getId();
        if (!$this->get('oro_segment.datagrid.configuration.provider')->isConfigurationValid($gridName)) {
            // unset grid name if invalid
            $gridName = false;
        }

        return [
            'entity'       => $entity,
            'segmentGroup' => $segmentGroup,
            'gridName'     => $gridName
        ];
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
        $this->get('oro_segment.segment_provider')->setCurrentSegment($entity);

        if ($this->get('oro_segment.form.handler.segment')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.segment.entity.saved')
            );

            if ($this->getRequest()->get(Router::ACTION_PARAMETER) === 'save_and_refresh') {
                $this->get('oro_segment.static_segment_manager')->run($entity);
            }

            return $this->get('oro_ui.router')->redirectAfterSave(
                ['route' => 'oro_segment_update', 'parameters' => ['id' => $entity->getId()]],
                ['route' => 'oro_segment_view', 'parameters' => ['id' => $entity->getId()]],
                $entity
            );
        }

        return [
            'entity'   => $entity,
            'form'     => $this->get('oro_segment.form.segment')->createView(),
            'entities' => $this->get('oro_segment.entity_provider')->getEntities(),
            'metadata' => $this->get('oro_query_designer.query_designer.manager')->getMetadata('segment')
        ];
    }
}
