<?php

namespace Oro\Bundle\TagBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Oro\Bundle\TagBundle\Form\Handler\TaxonomyHandler;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tag taxonomies.
 */
class TaxonomyController extends AbstractController
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_taxonomy_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_taxonomy_view",
     *      type="entity",
     *      class="OroTagBundle:Taxonomy",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
        return [
            'entity_class' => Taxonomy::class
        ];
    }

    /**
     * @Route("/create", name="oro_taxonomy_create")
     * @Acl(
     *      id="oro_taxonomy_create",
     *      type="entity",
     *      class="OroTagBundle:Taxonomy",
     *      permission="CREATE"
     * )
     * @Template("@OroTag/Taxonomy/update.html.twig")
     */
    public function createAction(Request $request)
    {
        return $this->update(new Taxonomy(), $request);
    }

    /**
     * @Route("/update/{id}", name="oro_taxonomy_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Acl(
     *      id="oro_taxonomy_update",
     *      type="entity",
     *      class="OroTagBundle:Taxonomy",
     *      permission="EDIT"
     * )
     * @Template
     */
    public function updateAction(Taxonomy $entity, Request $request)
    {
        return $this->update($entity, $request);
    }

    /**
     * @Route("/view/{id}", name="oro_taxonomy_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_taxonomy_view",
     *      type="entity",
     *      class="OroTagBundle:Taxonomy",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function viewAction(Taxonomy $entity)
    {
        return [
            'entity' => $entity
        ];
    }

    /**
     * @Route("/widget/info/{id}", name="oro_taxonomy_widget_info", requirements={"id"="\d+"})
     * @AclAncestor("oro_taxonomy_view")
     * @Template()
     */
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
        if ($this->get(TaxonomyHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.taxonomy.controller.saved.message')
            );

            return $this->get(Router::class)->redirect($entity);
        }

        return [
            'entity' => $entity,
            'form' => $this->get('oro_tag.form.taxonomy')->createView(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
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
