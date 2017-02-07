<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model;

use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Yaml\Yaml;

class OperationRegistryTest extends WebTestCase
{
    const ROOT_NODE_NAME = 'operations';

    /** @var FilesystemCache */
    private $cacheProvider;

    /** @var OperationRegistry */
    private $operationRegistry;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitions::class]);
        $this->setUpTokenStorage();

        $this->cacheProvider = $this->getContainer()->get('oro_action.cache.provider.operations');
        $this->cacheProvider->save(self::ROOT_NODE_NAME, $this->getOperationsConfig());

        $this->operationRegistry = $this->getContainer()->get('oro_action.operation_registry');
    }

    protected function tearDown()
    {
        $this->cacheProvider->delete(self::ROOT_NODE_NAME);
    }

    public function testFindWithDisablingFromWorkflows()
    {
        $operations = $this->operationRegistry->find(
            new OperationFindCriteria(
                'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                'test_operation_route',
                'test_operation_datagrid'
            )
        );

        $this->assertCount(1, $operations);

        /** @var Operation $operation */
        $operation = array_shift($operations);

        $this->assertEquals('oro_test_operation_not_disabled', $operation->getName());
    }

    protected function setUpTokenStorage()
    {
        $token = new UsernamePasswordToken(new User(), self::AUTH_PW, 'user');

        $this->getContainer()->get('security.token_storage')->setToken($token);
    }

    /**
     * @return array
     */
    protected function getOperationsConfig()
    {
        $config = Yaml::parse(file_get_contents(__DIR__ . '/../DataFixtures/config/oro/actions.yml')) ?: [];

        return isset($config['operations']) ? $config['operations'] : [];
    }
}
