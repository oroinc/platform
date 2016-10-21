<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Menu\Provider;

use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class GlobalOwnershipProviderTest extends WebTestCase
{
    /** @var GlobalOwnershipProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData'
        ]);

        $this->provider = $this->getContainer()->get('oro_navigation.ownership_provider.global');
    }

    public function testGetMenuUpdates()
    {
        $updates = $this->provider->getMenuUpdates('application_menu');

        $this->assertCount(5, $updates);
    }
}
