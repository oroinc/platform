<?php

namespace Oro\Bundle\PlatformBundle\Controller;

use Composer\Composer;
use Composer\Package\PackageInterface;

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
     * @Route("/about", name="oro_platform_about")
     * @Template()
     *
     * @Acl(
     *     id="oro_platform_about",
     *     label="oro.platform.about",
     *     type="action"
     * )
     */
    public function aboutAction()
    {
        $packages    = $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
        $oroPackages = $thirdPartyPackages = [];

        foreach ($packages as $package) {
            /** @var PackageInterface $package */
            if (0 === strpos($package->getName(), self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER)) {
                $oroPackages[] = $package;
            } else {
                $thirdPartyPackages [] = $package;
            }
        }

        return [
            'thirdPartyPackages' => $thirdPartyPackages,
            'oroPackages'        => $oroPackages
        ];
    }

    /**
     * @return Composer
     */
    protected function getComposer()
    {
        return $this->get('oro_platform.composer');
    }
}
