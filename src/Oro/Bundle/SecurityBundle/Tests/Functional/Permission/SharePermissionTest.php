<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Permission;

use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group CommunityEdition
 */
class SharePermissionTest extends WebTestCase
{
    public function testSharePermissionDoesNotExist()
    {
        if (class_exists('Oro\Bundle\PlatformProBundle\OroPlatformProBundle')) {
            $this->markTestSkipped('Test should be run on community edition application only.');
        }

        $this->initClient();
        $permissions = $this->getContainer()->get('doctrine')
            ->getRepository(Permission::class)
            ->findBy(['name' => 'SHARE']);

        $this->assertCount(0, $permissions);
    }
}
