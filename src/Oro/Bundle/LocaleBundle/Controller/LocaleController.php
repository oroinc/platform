<?php

namespace Oro\Bundle\LocaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;

use Oro\Bundle\LocaleBundle\Entity\Locale;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class LocaleController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_locale_locale_view", requirements={"id"="\d+"})
     * @Template
     * Acl(
     *      id="oro_locale_locale_view",
     *      type="entity",
     *      class="OroLocaleBundle:Locale",
     *      permission="VIEW"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Locale $locale
     * @return array
     */
    public function viewAction(Locale $locale)
    {
        return [
            'entity' => $locale
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_locale_locale_info", requirements={"id"="\d+"})
     * @Template
     * AclAncestor("oro_locale_locale_view")
     *
     * @param Locale $locale
     *
     * @return array
     */
    public function infoAction(Locale $locale)
    {
        return [
            'entity' => $locale,
        ];
    }

    /**
     * @Route("/", name="oro_locale_locale_index")
     * @Template
     * AclAncestor("oro_locale_locale_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_locale.entity.locale.class')
        ];
    }

    /**
     * @Route("/create", name="oro_locale_locale_create")
     * @Template("OroLocaleBundle:Locale:update.html.twig")
     * Acl(
     *     id="oro_locale_locale_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroLocaleBundle:Locale"
     * )
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new Locale(), $request);
    }

    /**
     * @Route("/update/{id}", name="oro_locale_locale_update", requirements={"id"="\d+"})
     * @Template
     * Acl(
     *     id="oro_locale_locale_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroLocaleBundle:Locale"
     * )
     *
     * @param Locale $locale
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Locale $locale, Request $request)
    {
        return $this->update($locale, $request);
    }

    /**
     * @param Locale $locale
     * @return array|RedirectResponse
     */
    protected function update(Locale $locale)
    {
        $form = $this->createFormBuilder($locale)
            ->add('name')
            ->add('language')
            ->add('format')
            ->getForm();

        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        return $handler->handleUpdate(
            $locale,
            $form,
            function (Locale $locale) {
                return [
                    'route'         => 'oro_locale_locale_update',
                    'parameters'    => ['id' => $locale->getId()]
                ];
            },
            function (Locale $locale) {
                return [
                    'route'         => 'oro_locale_locale_view',
                    'parameters'    => ['id' => $locale->getId()]
                ];
            },
            $this->get('translator')->trans('oro.locale.controller.locale.saved.message')
        );
    }
}
