<?php

namespace Oro\Bundle\PlatformBundle\Controller;

use Composer\Package\PackageInterface;

use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Route("/platform")
 */
class PlatformController extends Controller
{
    const ORO_NAMESPACE       = 'oro';
    const NAMESPACE_DELIMITER = '/';

    /**
     * @Route("/information", name="oro_platform_system_info")
     * @Template()
     *
     * @Acl(
     *     id="oro_platform_system_info",
     *     label="oro.platform.system_info",
     *     type="action"
     * )
     */
    public function systemInfoAction()
    {
        $packages    = $this->getLocalRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $oroPackages = $thirdPartyPackages = [];

        foreach ($packages as $package) {
            /** @var PackageInterface $package */
            if (0 === strpos($package->getName(), self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER)) {
                $oroPackages[] = $package;
            } else {
                $thirdPartyPackages[] = $package;
            }
        }

        return [
            'thirdPartyPackages' => $thirdPartyPackages,
            'oroPackages'        => $oroPackages
        ];
    }

    /**
     * @return LocalRepositoryManager
     */
    protected function getLocalRepositoryManager()
    {
        return $this->get('oro_platform.composer.local_repo_manager');
    }
}
