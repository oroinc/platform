<?php

namespace Oro\Bundle\PlatformBundle\Controller;

use Oro\Bundle\PlatformBundle\Provider\DeploymentVariableProvider;
use Oro\Bundle\PlatformBundle\Provider\PackageProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller provide information about installed packages
 *
 * @Route("/platform")
 */
class PlatformController extends AbstractController
{
    /**
     * @Route("/information", name="oro_platform_system_info")
     * @Template()
     *
     * @Acl(
     *     id="oro_platform_system_info",
     *     label="oro.platform.acl.action.system_info.label",
     *     type="action",
     *     category="platform"
     * )
     */
    public function systemInfoAction()
    {
        $packageProvider = $this->get(PackageProvider::class);

        return [
            'thirdPartyPackages' => $packageProvider->getThirdPartyPackages(),
            'oroPackages' => $packageProvider->getOroPackages(),
            'deploymentVariables' => $this->get(DeploymentVariableProvider::class)->getVariables(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            PackageProvider::class,
            DeploymentVariableProvider::class
        ]);
    }
}
