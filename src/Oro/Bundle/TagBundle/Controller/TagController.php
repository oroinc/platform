<?php

namespace Oro\Bundle\TagBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Form\Handler\TagHandler;
use Oro\Bundle\TagBundle\Provider\StatisticProvider;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tags.
 */
class TagController extends AbstractController
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
        return [
            'entity_class' => Tag::class
        ];
    }

    /**
     * @Route("/create", name="oro_tag_create")
     * @Acl(
     *      id="oro_tag_create",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="CREATE"
     * )
     * @Template("@OroTag/Tag/update.html.twig")
     */
    public function createAction(Request $request)
    {
        return $this->update(new Tag(), $request);
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
    public function updateAction(Tag $entity, Request $request)
    {
        return $this->update($entity, $request);
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

        $provider       = $this->get(StatisticProvider::class);
        $groupedResults = $provider->getTagEntitiesStatistic($entity);
        $selectedResult = null;

        foreach ($groupedResults as $alias => $type) {
            if ($alias === $from) {
                $selectedResult = $type;
                break;
            }
        }

        return [
            'tag'            => $entity,
            'from'           => $from,
            'groupedResults' => $groupedResults,
            'selectedResult' => $selectedResult
        ];
    }

    /**
     * @param Tag $entity
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Tag $entity, Request $request)
    {
        if ($this->get(TagHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.tag.controller.tag.saved.message')
            );

            return $this->get(Router::class)->redirect($entity);
        }

        return [
            'entity' => $entity,
            'form' => $this->get('oro_tag.form.tag')->createView(),
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
                TagHandler::class,
                StatisticProvider::class,
                'oro_tag.form.tag' => Form::class,
            ]
        );
    }
}
