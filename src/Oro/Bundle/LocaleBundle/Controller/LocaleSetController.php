<?php

namespace Oro\Bundle\LocaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;

use Oro\Bundle\LocaleBundle\Entity\LocaleSet;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class LocaleSetController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_locale_localeset_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_locale_localeset_view",
     *      type="entity",
     *      class="OroLocaleBundle:LocaleSet",
     *      permission="VIEW"
     * )
     *
     * @param LocaleSet $localeSet
     * @return array
     */
    public function viewAction(LocaleSet $localeSet)
    {
        return [
            'entity' => $localeSet
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_locale_localeset_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_locale_localeset_view")
     *
     * @param LocaleSet $localeSet
     *
     * @return array
     */
    public function infoAction(LocaleSet $localeSet)
    {
        return [
            'entity' => $localeSet,
        ];
    }

    /**
     * @Route("/", name="oro_locale_localeset_index")
     * @Template
     * @AclAncestor("oro_locale_localeset_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_locale.entity.locale_set.class')
        ];
    }

    /**
     * @Route("/create", name="oro_locale_localeset_create")
     * @Template("OroLocaleBundle:LocaleSet:update.html.twig")
     * @Acl(
     *     id="oro_locale_localeset_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroLocaleBundle:LocaleSet"
     * )
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new LocaleSet(), $request);
    }

    /**
     * @Route("/update/{id}", name="oro_locale_localeset_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="oro_locale_localeset_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroLocaleBundle:LocaleSet"
     * )
     *
     * @param LocaleSet $localeSet
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function updateAction(LocaleSet $localeSet, Request $request)
    {
        return $this->update($localeSet, $request);
    }

    /**
     * @param LocaleSet $localeSet
     * @return array|RedirectResponse
     */
    protected function update(LocaleSet $localeSet)
    {
        $form = $this->createFormBuilder($localeSet)
            ->add('name')
            ->add('i18nCode')
            ->add('l10nCode')
            ->add('parentLocaleSet', 'entity', [
                'class' => 'Oro\Bundle\LocaleBundle\Entity\LocaleSet',
                'required' => false,
            ])
            ->getForm();

        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        return $handler->handleUpdate(
            $localeSet,
            $form,
            function (LocaleSet $localeSet) {
                return [
                    'route'         => 'oro_locale_localeset_update',
                    'parameters'    => ['id' => $localeSet->getId()]
                ];
            },
            function (LocaleSet $localeSet) {
                return [
                    'route'         => 'oro_locale_localeset_view',
                    'parameters'    => ['id' => $localeSet->getId()]
                ];
            },
            $this->get('translator')->trans('oro.locale.controller.localeset.saved.message')
        );
    }
}
