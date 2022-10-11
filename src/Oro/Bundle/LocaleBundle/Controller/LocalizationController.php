<?php

namespace Oro\Bundle\LocaleBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for localizations.
 */
class LocalizationController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_locale_localization_view", requirements={"id"="\d+"})
     * @Template("@OroLocale/Localization/view.html.twig")
     * @Acl(
     *      id="oro_locale_localization_view",
     *      type="entity",
     *      class="OroLocaleBundle:Localization",
     *      permission="VIEW"
     * )
     */
    public function viewAction(Localization $localization): array
    {
        return [
            'entity' => $localization
        ];
    }

    /**
     * @Route("/", name="oro_locale_localization_index")
     * @Template("@OroLocale/Localization/index.html.twig")
     * @AclAncestor("oro_locale_localization_view")
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => Localization::class
        ];
    }

    /**
     * @Route("/create", name="oro_locale_localization_create")
     * @Template("@OroLocale/Localization/update.html.twig")
     * @Acl(
     *     id="oro_locale_localization_create",
     *     type="entity",
     *     permission="CREATE",
     *     class="OroLocaleBundle:Localization"
     * )
     */
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new Localization());
    }

    /**
     * @Route("/update/{id}", name="oro_locale_localization_update", requirements={"id"="\d+"})
     * @Template("@OroLocale/Localization/update.html.twig")
     * @Acl(
     *     id="oro_locale_localization_update",
     *     type="entity",
     *     permission="EDIT",
     *     class="OroLocaleBundle:Localization"
     * )
     */
    public function updateAction(Localization $localization): array|RedirectResponse
    {
        return $this->update($localization);
    }
    
    protected function update(Localization $localization): array|RedirectResponse
    {
        return $this->get(UpdateHandlerFacade::class)->update(
            $localization,
            $this->createForm(LocalizationType::class, $localization),
            $this->get(TranslatorInterface::class)->trans('oro.locale.controller.localization.saved.message')
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandlerFacade::class
            ]
        );
    }
}
