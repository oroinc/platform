<?php

namespace Oro\Bundle\TagBundle\Controller;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Oro\Bundle\TagBundle\Form\Handler\TaxonomyHandler;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tag taxonomies.
 */
class TaxonomyController extends AbstractController
{
    #[Route(
        path: '/{_format}',
        name: 'oro_taxonomy_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template]
    #[Acl(id: 'oro_taxonomy_view', type: 'entity', class: Taxonomy::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [
            'entity_class' => Taxonomy::class
        ];
    }

    #[Route(path: '/create', name: 'oro_taxonomy_create')]
    #[Template('@OroTag/Taxonomy/update.html.twig')]
    #[Acl(id: 'oro_taxonomy_create', type: 'entity', class: Taxonomy::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new Taxonomy(), $request);
    }

    #[Route(path: '/update/{id}', name: 'oro_taxonomy_update', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template]
    #[Acl(id: 'oro_taxonomy_update', type: 'entity', class: Taxonomy::class, permission: 'EDIT')]
    public function updateAction(Taxonomy $entity, Request $request)
    {
        return $this->update($entity, $request);
    }

    #[Route(path: '/view/{id}', name: 'oro_taxonomy_view', requirements: ['id' => '\d+'])]
    #[Template]
    #[Acl(id: 'oro_taxonomy_view', type: 'entity', class: Taxonomy::class, permission: 'VIEW')]
    public function viewAction(Taxonomy $entity)
    {
        return [
            'entity' => $entity
        ];
    }

    #[Route(path: '/widget/info/{id}', name: 'oro_taxonomy_widget_info', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_taxonomy_view')]
    public function infoAction(Taxonomy $taxonomy)
    {
        return [
            'taxonomy' => $taxonomy
        ];
    }

    /**
     * @param Taxonomy $entity
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Taxonomy $entity, Request $request)
    {
        if ($this->container->get(TaxonomyHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.taxonomy.controller.saved.message')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity' => $entity,
            'form' => $this->container->get('oro_tag.form.taxonomy')->createView(),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                'oro_tag.form.taxonomy' => Form::class,
                TaxonomyHandler::class,
            ]
        );
    }
}
