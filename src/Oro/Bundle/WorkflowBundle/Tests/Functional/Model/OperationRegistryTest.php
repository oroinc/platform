<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Yaml\Yaml;

class OperationRegistryTest extends WebTestCase
{
    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var PhpArrayConfigCacheModifier */
    private $configModifier;

    /** @var OperationRegistry */
    private $operationRegistry;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitions::class]);
        $this->setUpTokenStorage();

        $this->configProvider = $this->getContainer()->get('oro_action.tests.configuration.provider');
        $this->configModifier = new PhpArrayConfigCacheModifier($this->configProvider);
        $this->setOperationsConfig($this->getOperationsConfig());

        $this->operationRegistry = $this->getContainer()->get('oro_action.operation_registry');
    }

    protected function tearDown(): void
    {
        $this->configModifier->resetCache();
    }

    public function testFindWithDisablingFromWorkflows()
    {
        $operations = $this->operationRegistry->find(
            new OperationFindCriteria(Item::class, 'test_operation_route', 'test_operation_datagrid')
        );

        $this->assertCount(1, $operations);

        /** @var Operation $operation */
        $operation = array_shift($operations);

        $this->assertEquals('oro_test_operation_not_disabled', $operation->getName());
    }

    private function setUpTokenStorage()
    {
        $token = new UsernamePasswordToken(new User(), self::AUTH_PW, 'user');

        $this->getContainer()->get('security.token_storage')->setToken($token);
    }

    private function getOperationsConfig(): array
    {
        $config = Yaml::parse(file_get_contents(__DIR__ . '/../DataFixtures/config/oro/actions.yml')) ?: [];

        return $config['operations'] ?? [];
    }

    private function setOperationsConfig(array $operations)
    {
        $config = $this->configProvider->getConfiguration();
        $config['operations'] = $operations;
        $this->configModifier->updateCache($config);
    }
}
