<?php

namespace Oro\Bundle\LocaleBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for localizations.
 */
class LocalizationController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_locale_localization_view', requirements: ['id' => '\d+'])]
    #[Template('@OroLocale/Localization/view.html.twig')]
    #[Acl(id: 'oro_locale_localization_view', type: 'entity', class: Localization::class, permission: 'VIEW')]
    public function viewAction(Localization $localization): array
    {
        return [
            'entity' => $localization
        ];
    }

    #[Route(path: '/', name: 'oro_locale_localization_index')]
    #[Template('@OroLocale/Localization/index.html.twig')]
    #[AclAncestor('oro_locale_localization_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => Localization::class
        ];
    }

    #[Route(path: '/create', name: 'oro_locale_localization_create')]
    #[Template('@OroLocale/Localization/update.html.twig')]
    #[Acl(id: 'oro_locale_localization_create', type: 'entity', class: Localization::class, permission: 'CREATE')]
    public function createAction(): array|RedirectResponse
    {
        return $this->update(new Localization());
    }

    #[Route(path: '/update/{id}', name: 'oro_locale_localization_update', requirements: ['id' => '\d+'])]
    #[Template('@OroLocale/Localization/update.html.twig')]
    #[Acl(id: 'oro_locale_localization_update', type: 'entity', class: Localization::class, permission: 'EDIT')]
    public function updateAction(Localization $localization): array|RedirectResponse
    {
        return $this->update($localization);
    }

    protected function update(Localization $localization): array|RedirectResponse
    {
        return $this->container->get(UpdateHandlerFacade::class)->update(
            $localization,
            $this->createForm(LocalizationType::class, $localization),
            $this->container->get(TranslatorInterface::class)->trans('oro.locale.controller.localization.saved.message')
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
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
