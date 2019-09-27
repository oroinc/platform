<?php

namespace Oro\Bundle\DigitalAssetBundle\Controller;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

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
}
