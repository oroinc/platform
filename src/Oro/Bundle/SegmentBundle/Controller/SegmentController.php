<?php

namespace Oro\Bundle\SegmentBundle\Controller;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Handler\SegmentHandler;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentType;
use Oro\Bundle\SegmentBundle\Grid\ConfigurationProvider;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Covers the CRUD functionality and the additional operation clone for the Segment entity.
 */
class SegmentController extends AbstractController
{
    /**
     * @return array
     */
    #[Route(
        path: '/{_format}',
        name: 'oro_segment_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template]
    #[AclAncestor('oro_segment_view')]
    public function indexAction()
    {
        return [];
    }

    /**
     * @param Segment $entity
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_segment_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_segment_view', type: 'entity', class: Segment::class, permission: 'VIEW')]
    public function viewAction(Segment $entity)
    {
        $this->checkSegment($entity);

        $this->container->get(EntityNameProvider::class)->setCurrentItem($entity);

        $segmentGroup = $this->container->get(ConfigManager::class)
            ->getEntityConfig('entity', $entity->getEntity())
            ->get('plural_label');

        $gridName = $entity::GRID_PREFIX . $entity->getId();
        if (!$this->container->get(ConfigurationProvider::class)->isConfigurationValid($gridName)) {
            // unset grid name if invalid
            $gridName = false;
        }

        return [
            'entity'       => $entity,
            'segmentGroup' => $segmentGroup,
            'gridName'     => $gridName
        ];
    }

    #[Route(path: '/create', name: 'oro_segment_create')]
    #[Template('@OroSegment/Segment/update.html.twig')]
    #[Acl(id: 'oro_segment_create', type: 'entity', class: Segment::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new Segment(), $request);
    }

    /**
     *
     *
     * @param Segment $entity
     * @param Request $request
     * @return array
     */
    #[Route(path: '/update/{id}', name: 'oro_segment_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_segment_update', type: 'entity', class: Segment::class, permission: 'EDIT')]
    public function updateAction(Segment $entity, Request $request)
    {
        $this->checkSegment($entity);

        return $this->update($entity, $request);
    }

    /**
     *
     * @param Segment $entity
     * @param Request $request
     * @return array
     */
    #[Route(path: '/clone/{id}', name: 'oro_segment_clone', requirements: ['id' => '\d+'])]
    #[Template('@OroSegment/Segment/update.html.twig')]
    #[AclAncestor('oro_segment_create')]
    public function cloneAction(Segment $entity, Request $request)
    {
        $this->checkSegment($entity);

        $clonedEntity = clone $entity;
        $clonedEntity->setName(
            $this->container->get(TranslatorInterface::class)->trans(
                'oro.segment.action.clone.name_format',
                [
                    '{name}' => $clonedEntity->getName()
                ]
            )
        );

        return $this->update($clonedEntity, $request);
    }

    /**
     *
     * @param Segment $entity
     * @param Request $request
     * @return RedirectResponse
     */
    #[Route(path: '/refresh/{id}', name: 'oro_segment_refresh', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_segment_update')]
    public function refreshAction(Segment $entity, Request $request)
    {
        $this->checkSegment($entity);

        if ($entity->isStaticType()) {
            $this->container->get(StaticSegmentManager::class)->run($entity);

            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.segment.refresh_dialog.success')
            );
        }

        return $this->redirectToRoute('oro_segment_view', ['id' => $entity->getId()]);
    }

    /**
     * @param Segment $entity
     * @param Request $request
     *
     * @return array
     */
    protected function update(Segment $entity, Request $request)
    {
        $form = $this->container->get('form.factory')
            ->createNamed('oro_segment_form', SegmentType::class);

        if ($this->container->get(SegmentHandler::class)->process($form, $entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.segment.entity.saved')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity'   => $entity,
            'form'     => $form->createView(),
            'entities' => $this->container->get(EntityProvider::class)->getEntities(),
            'metadata' => $this->container->get(Manager::class)->getMetadata('segment')
        ];
    }

    protected function checkSegment(Segment $segment)
    {
        if ($segment->getEntity() &&
            !$this->container->get(FeatureChecker::class)->isResourceEnabled($segment->getEntity(), 'entities')
        ) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                EntityProvider::class,
                ConfigManager::class,
                FeatureChecker::class,
                ConfigurationProvider::class,
                TranslatorInterface::class,
                Router::class,
                StaticSegmentManager::class,
                SegmentHandler::class,
                Manager::class,
                EntityNameProvider::class,
                'form.factory' => FormFactoryInterface::class,
            ]
        );
    }
}
