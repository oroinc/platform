<?php

namespace Oro\Bundle\PlatformBundle\Controller;

use Oro\Bundle\PlatformBundle\Provider\DeploymentVariableProvider;
use Oro\Bundle\PlatformBundle\Provider\PackageProvider;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\UsageStatsProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller provide information about installed packages
 *
 * @Route("/platform")
 */
class PlatformController
{
    private PackageProvider $packageProvider;
    private DeploymentVariableProvider $deploymentVariableProvider;
    private UsageStatsProvider $usageStatsProvider;

    public function __construct(
        PackageProvider $packageProvider,
        DeploymentVariableProvider $deploymentVariableProvider,
        UsageStatsProvider $usageStatsProvider
    ) {
        $this->packageProvider = $packageProvider;
        $this->deploymentVariableProvider = $deploymentVariableProvider;
        $this->usageStatsProvider = $usageStatsProvider;
    }

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
        return [
            'thirdPartyPackages' => $this->packageProvider->getThirdPartyPackages(),
            'oroPackages' => $this->packageProvider->getOroPackages(),
            'deploymentVariables' => $this->deploymentVariableProvider->getVariables(),
            'usageStats' => $this->usageStatsProvider->getUsageStats(),
        ];
    }
}
