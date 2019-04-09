<?php

namespace Oro\Bundle\SegmentBundle\Controller;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Covers the CRUD functionality and the additional operation clone for the Segment entity.
 */
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
     * @Route("/view/{id}", name="oro_segment_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_segment_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroSegmentBundle:Segment"
     * )
     * @Template
     *
     * @param Segment $entity
     * @return array
     */
    public function viewAction(Segment $entity)
    {
        $this->checkSegment($entity);

        $this->get('oro_segment.entity_name_provider')->setCurrentItem($entity);

        $segmentGroup = $this->get('oro_entity_config.provider.entity')
            ->getConfig($entity->getEntity())
            ->get('plural_label');

        $gridName = $entity::GRID_PREFIX . $entity->getId();
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
     * @Route("/update/{id}", name="oro_segment_update", requirements={"id"="\d+"})
     *
     * @Template
     * @Acl(
     *      id="oro_segment_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroSegmentBundle:Segment"
     * )
     *
     * @param Segment $entity
     * @return array
     */
    public function updateAction(Segment $entity)
    {
        $this->checkSegment($entity);

        return $this->update($entity);
    }

    /**
     * @Route("/clone/{id}", name="oro_segment_clone", requirements={"id"="\d+"})
     * @Template("OroSegmentBundle:Segment:update.html.twig")
     * @AclAncestor("oro_segment_create")
     *
     * @param Segment $entity
     * @return array
     */
    public function cloneAction(Segment $entity)
    {
        $this->checkSegment($entity);

        $clonedEntity = clone $entity;
        $clonedEntity->setName(
            $this->get('translator')->trans(
                'oro.segment.action.clone.name_format',
                [
                    '{name}' => $clonedEntity->getName()
                ]
            )
        );

        return $this->update($clonedEntity);
    }

    /**
     * @Route("/refresh/{id}", name="oro_segment_refresh", requirements={"id"="\d+"})
     * @AclAncestor("oro_segment_update")
     *
     * @param Segment $entity
     * @return RedirectResponse
     */
    public function refreshAction(Segment $entity)
    {
        $this->checkSegment($entity);

        if ($entity->isStaticType()) {
            $this->get('oro_segment.static_segment_manager')->run($entity);

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.segment.refresh_dialog.success')
            );
        }

        return $this->redirectToRoute('oro_segment_view', ['id' => $entity->getId()]);
    }

    /**
     * @param Segment $entity
     *
     * @return array
     */
    protected function update(Segment $entity)
    {
        if ($this->get('oro_segment.form.handler.segment')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.segment.entity.saved')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return [
            'entity'   => $entity,
            'form'     => $this->get('oro_segment.form.segment')->createView(),
            'entities' => $this->get('oro_segment.entity_provider')->getEntities(),
            'metadata' => $this->get('oro_query_designer.query_designer.manager')->getMetadata('segment')
        ];
    }

    /**
     * @param Segment $segment
     */
    protected function checkSegment(Segment $segment)
    {
        if ($segment->getEntity() &&
            !$this->getFeatureChecker()->isResourceEnabled($segment->getEntity(), 'entities')
        ) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @return FeatureChecker
     */
    protected function getFeatureChecker()
    {
        return $this->get('oro_featuretoggle.checker.feature_checker');
    }
}
