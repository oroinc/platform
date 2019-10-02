<?php

namespace Oro\Bundle\DigitalAssetBundle\Controller;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Form\Type\DigitalAssetType;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for DigitalAsset entity.
 */
class DigitalAssetController extends AbstractController
{
    /**
     * @Route("/", name="oro_digital_asset_index")
     * @Template("@OroDigitalAsset/DigitalAsset/index.html.twig")
     * @Acl(
     *      id="oro_digital_asset_view",
     *      type="entity",
     *      class="OroDigitalAssetBundle:DigitalAsset",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => DigitalAsset::class,
        ];
    }

    /**
     * @Route("/create", name="oro_digital_asset_create")
     * @Template("@OroDigitalAsset/DigitalAsset/update.html.twig")
     * @Acl(
     *      id="oro_digital_asset_create",
     *      type="entity",
     *      class="OroDigitalAssetBundle:DigitalAsset",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new DigitalAsset());
    }

    /**
     * @Route("/update/{id}", name="oro_digital_asset_update", requirements={"id"="\d+"})
     * @Template("@OroDigitalAsset/DigitalAsset/update.html.twig")
     * @Acl(
     *      id="oro_digital_asset_update",
     *      type="entity",
     *      class="OroDigitalAssetBundle:DigitalAsset",
     *      permission="EDIT"
     * )
     *
     * @param DigitalAsset $digitalAsset
     *
     * @return array|RedirectResponse
     */
    public function updateAction(DigitalAsset $digitalAsset)
    {
        return $this->update($digitalAsset);
    }

    /**
     * @Route("/widget/select-data-asset", name="oro_digital_asset_select_widget")
     */
    public function selectDataAssetWidgetAction()
    {
        die('<div class="widget-content">TODO</div>');
    }

    /**
     * @param DigitalAsset $digitalAsset
     *
     * @return array|RedirectResponse
     */
    protected function update(DigitalAsset $digitalAsset)
    {
        return $this->get(UpdateHandlerFacade::class)
            ->update(
                $digitalAsset,
                $this->createForm(DigitalAssetType::class, $digitalAsset),
                $this->get(TranslatorInterface::class)->trans('oro.digitalasset.controller.saved.message')
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
            ]
        );
    }
}
