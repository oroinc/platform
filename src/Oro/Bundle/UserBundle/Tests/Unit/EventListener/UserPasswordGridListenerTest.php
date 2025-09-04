<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UserBundle\EventListener\UserPasswordGridListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserPasswordGridListenerTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private UserPasswordGridListener $userPasswordGridListener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->userPasswordGridListener = new UserPasswordGridListener($this->featureChecker);
    }

    public function testOnBuildAfterWithEnabledFeature(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new BuildAfter($datagrid);

        $datagrid->expects(self::never())
            ->method('getConfig');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(true);

        $this->userPasswordGridListener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithDisabledFeature(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);
        $event = new BuildAfter($datagrid);

        $datagrid->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects(self::once())
            ->method('removeColumn')
            ->with('auth_status');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('user_login_password')
            ->willReturn(false);

        $this->userPasswordGridListener->onBuildAfter($event);
    }
}
