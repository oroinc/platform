<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class UsersUsageStatsProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadUserData::class,
            ]
        );
    }

    public function testGetUsersUsageStatsValue(): void
    {
        $provider = $this->getContainer()->get('oro_user.provider.users_usage_stats_provider');

        self::assertSame('4', $provider->getValue());
    }
}
