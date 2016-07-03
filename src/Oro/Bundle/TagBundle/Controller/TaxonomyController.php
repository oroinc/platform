<?php

namespace Oro\Bundle\TagBundle\Controller;

use Oro\Bundle\TagBundle\Entity\Taxonomy;
use Oro\Bundle\TagBundle\Entity\Taxonomys;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

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
     * @param Taxonomy $entity
     * @return array|RedirectResponse
     */
    protected function update(Taxonomy $entity)
    {
        if ($this->get('oro_tag.form.handler.taxonomy')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.tag.controller.taxonomy.saved.message')
            );

            return $this->redirect($this->generateUrl('oro_taxonomy_index'));
        }

        return array(
            'entity' => $entity,
            'form' => $this->get('oro_tag.form.taxonomy')->createView(),
        );
    }
}
