<?php

namespace Oro\Bundle\TagBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class TagController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_tag_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_tag_view",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
        return array(
            'entity_class' => $this->container->getParameter('oro_tag.tag.entity.class')
        );
    }

    /**
     * @Route("/create", name="oro_tag_create")
     * @Acl(
     *      id="oro_tag_create",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="CREATE"
     * )
     * @Template("OroTagBundle:Tag:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Tag());
    }

    /**
     * @Route("/update/{id}", name="oro_tag_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Acl(
     *      id="oro_tag_update",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="EDIT"
     * )
     * @Template
     */
    public function updateAction(Tag $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route("/search/{id}", name="oro_tag_search", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     * @AclAncestor("oro_tag_view")
     */
    public function searchAction(Tag $entity, Request $request)
    {
        // path to datagrid subrequest
        $from = $request->get('from');

        $provider       = $this->get('oro_tag.provider.statistic');
        $groupedResults = $provider->getTagEntitiesStatistic($entity);
        $selectedResult = null;

        foreach ($groupedResults as $alias => $type) {
            if ($alias === $from) {
                $selectedResult = $type;
                break;
            }
        }

        return array(
            'tag'            => $entity,
            'from'           => $from,
            'groupedResults' => $groupedResults,
            'selectedResult' => $selectedResult
        );
    }

    /**
     * @param Tag $entity
     * @return array|RedirectResponse
     */
    protected function update(Tag $entity)
    {
        if ($this->get('oro_tag.form.handler.tag')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.tag.controller.tag.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return array(
            'entity' => $entity,
            'form' => $this->get('oro_tag.form.tag')->createView(),
        );
    }
}
