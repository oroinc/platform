<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Functional\EventListener;

use Oro\Bundle\FeatureToggleBundle\Tests\Functional\Stub\FeatureCheckerStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConsoleCommandListenerTest extends WebTestCase
{
    public function testDisabledCommandByFeature(): void
    {
        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceEnabled('oro:feature-toggle:config:dump-reference', 'commands', false);
        try {
            $result = $this->runCommand('oro:feature-toggle:config:dump-reference');
        } finally {
            $featureChecker->setResourceEnabled('oro:feature-toggle:config:dump-reference', 'commands', null);
        }

        self::assertStringContainsString('The feature that enables this command is turned off.', $result);
    }
}
