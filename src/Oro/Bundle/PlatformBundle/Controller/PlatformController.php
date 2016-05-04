<?php

namespace Oro\Bundle\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\PlatformBundle\Helper\PackageHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Route("/platform")
 */
class PlatformController extends Controller
{
    /** @deprecated since 1.10 */
    const ORO_NAMESPACE = PackageHelper::ORO_NAMESPACE;

    /** @deprecated since 1.10 */
    const NAMESPACE_DELIMITER = PackageHelper::NAMESPACE_DELIMITER;

    /**
     * @Route("/information", name="oro_platform_system_info")
     * @Template()
     *
     * @Acl(
     *     id="oro_platform_system_info",
     *     label="oro.platform.acl.action.system_info.label",
     *     type="action"
     * )
     */
    public function systemInfoAction()
    {
        $packageHelper = $this->get('oro_platform.helper.package');

        return [
            'thirdPartyPackages' => $packageHelper->getThirdPartyPackages(),
            'oroPackages' => $packageHelper->getOroPackages(),
        ];
    }
}
