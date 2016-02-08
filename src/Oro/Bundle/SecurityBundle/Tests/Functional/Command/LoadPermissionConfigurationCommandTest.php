<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Console\Tester\CommandTester;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\SecurityBundle\Entity\Permission;

/**
 * @dbIsolation
 */
class LoadPermissionConfigurationCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $provider = $this->getContainer()->get('oro_security.configuration.provider.permission_configuration');
        $reflection = new \ReflectionClass('Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider');
        $reflectionProperty = $reflection->getProperty('configPath');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($provider, '/Tests/Functional/Command/DataFixtures/permissions.yml');
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $expectedMessages
     * @param array $expectedPermissions
     */
    public function testExecute(array $expectedMessages, array $expectedPermissions)
    {
        $permissionsBefore = $this->getRepository('OroSecurityBundle:Permission')->findAll();
        $result = $this->runCommand(LoadPermissionConfigurationCommand::NAME);
        $this->assertNotEmpty($result);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }
        $permissions = $this->getRepository('OroSecurityBundle:Permission')->findAll();
        $this->assertCount(count($permissionsBefore) + 3, $permissions);
        foreach ($expectedPermissions as $permission) {
            $this->assertPermissionLoaded($permissions, $permission);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'expectedMessages' => [
                    'Loading permissions...',
                ],
                'expectedPermissions' => [
                    'TEST_PERMISSION1',
                    'TEST_PERMISSION2',
                    'TEST_PERMISSION3',
                ],
            ]
        ];
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className)->getRepository($className);
    }

    /**
     * @param array|Permission[] $permissions
     * @param string $name
     */
    protected function assertPermissionLoaded(array $permissions, $name)
    {
        $found = false;
        foreach ($permissions as $permission) {
            if ($permission->getName() === $name) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
}
