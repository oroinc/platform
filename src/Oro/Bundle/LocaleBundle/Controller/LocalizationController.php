<?php

namespace Oro\Bundle\LocaleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\FormBundle\Model\UpdateHandler;

use Oro\Bundle\LocaleBundle\Entity\Localization;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

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
     * @Route("/info/{id}", name="oro_locale_localization_info", requirements={"id"="\d+"})
     * @Template("OroLocaleBundle:Localization:widget\info.html.twig")
     * @AclAncestor("oro_locale_localization_view")
     *
     * @param Localization $localization
     *
     * @return array
     */
    public function infoAction(Localization $localization)
    {
        return [
            'entity' => $localization,
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
        return $this->update(new Localization());
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
        $form = $this->createForm('oro_localization', $localization);

        /* @var $handler UpdateHandler */
        $handler = $this->get('oro_form.model.update_handler');
        return $handler->handleUpdate(
            $localization,
            $form,
            function (Localization $localization) {
                return [
                    'route'         => 'oro_locale_localization_update',
                    'parameters'    => ['id' => $localization->getId()]
                ];
            },
            function (Localization $localization) {
                return [
                    'route'         => 'oro_locale_localization_view',
                    'parameters'    => ['id' => $localization->getId()]
                ];
            },
            $this->get('translator')->trans('oro.locale.controller.localization.saved.message')
        );
    }
}
