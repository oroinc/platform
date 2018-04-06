<?php

namespace Oro\Bundle\TagBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class TaxonomyController extends Controller
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
        return array(
            'entity_class' => $this->container->getParameter('oro_tag.taxonomy.entity.class')
        );
    }

    /**
     * @Route("/create", name="oro_taxonomy_create")
     * @Acl(
     *      id="oro_taxonomy_create",
     *      type="entity",
     *      class="OroTagBundle:Taxonomy",
     *      permission="CREATE"
     * )
     * @Template("OroTagBundle:Taxonomy:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Taxonomy());
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
    public function updateAction(Taxonomy $entity)
    {
        return $this->update($entity);
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
     * @return array|RedirectResponse
     */
    protected function update(Taxonomy $entity)
    {
        if ($this->get('oro_tag.form.handler.taxonomy')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.taxonomy.controller.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return array(
            'entity' => $entity,
            'form' => $this->get('oro_tag.form.taxonomy')->createView(),
        );
    }
}
