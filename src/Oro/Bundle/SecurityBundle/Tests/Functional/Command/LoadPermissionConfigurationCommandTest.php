<?php
namespace Oro\Bundle\SecurityBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Console\Tester\CommandTester;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SecurityBundle\Entity\PermissionDefinition;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;

/**
 * @dbIsolation
 */
class LoadPermissionConfigurationCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $provider = $this->getContainer()->get('oro_security.configuration.provider.permission_config');
        $reflection = new \ReflectionClass('Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider');
        $reflectionProperty = $reflection->getProperty('configPath');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($provider, '/Tests/Functional/Command/DataFixtures/permission.yml');
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $expectedMessages
     * @param array $expectedDefinitions
     */
    public function testExecute(array $expectedMessages, array $expectedDefinitions)
    {
        $definitionsBefore = $this->getRepository('OroSecurityBundle:PermissionDefinition')->findAll();
        $result = $this->runCommand(LoadPermissionConfigurationCommand::NAME);
        $this->assertNotEmpty($result);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }
        $definitions = $this->getRepository('OroSecurityBundle:PermissionDefinition')->findAll();
        $this->assertCount(count($definitionsBefore) + 3, $definitions);
        foreach ($expectedDefinitions as $definition) {
            $this->assertDefinitionLoaded($definitions, $definition);
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
                    'Loading permission definitions...',
                ],
                'expectedDefinitions' => [
                    'PERMISSION1',
                    'PERMISSION2',
                    'PERMISSION3',
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
     * @param array|PermissionDefinition[] $definitions
     * @param string $name
     */
    protected function assertDefinitionLoaded(array $definitions, $name)
    {
        $found = false;
        foreach ($definitions as $definition) {
            if ($definition->getName() === $name) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
}
