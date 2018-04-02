<?php

namespace Oro\Bundle\LocaleBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LocalizationController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_locale_localization_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_locale_localization_view",
     *      type="entity",
     *      class="OroLocaleBundle:Localization",
     *      permission="VIEW"
     * )
     *
     * @param Localization $localization
     * @return array
     */
    public function viewAction(Localization $localization)
    {
        return [
            'entity' => $localization
        ];
    }

    /**
     * @Route("/", name="oro_locale_localization_index")
     * @Template
     * @AclAncestor("oro_locale_localization_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_locale.entity.localization.class')
        ];
    }

    /**
     * @Route("/create", name="oro_locale_localization_create")
     * @Template("OroLocaleBundle:Localization:update.html.twig")
     * @Acl(
     *     id="oro_locale_localization_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroLocaleBundle:Localization"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        $response = $this->update(new Localization());

        if ($response instanceof RedirectResponse) {
            $message = $this->get('translator')->trans(
                'oro.translation.translation.rebuild_cache_required',
                [
                    '%path%' => $this->generateUrl('oro_translation_translation_index'),
                ]
            );
            $this->get('session')->getFlashBag()->add('warning', $message);
        }

        return $response;
    }

    /**
     * @Route("/update/{id}", name="oro_locale_localization_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *     id="oro_locale_localization_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroLocaleBundle:Localization"
     * )
     *
     * @param Localization $localization
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Localization $localization)
    {
        return $this->update($localization);
    }

    /**
     * @param Localization $localization
     * @return array|RedirectResponse
     */
    protected function update(Localization $localization)
    {
        $form = $this->createForm(LocalizationType::class, $localization);

        /** @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        return $handler->update(
            $localization,
            $form,
            $this->get('translator')->trans('oro.locale.controller.localization.saved.message')
        );
    }
}
