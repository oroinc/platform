<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Model;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyPath;

class ActionExecutorTest extends WebTestCase
{
    private ActionExecutor $executor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->executor = $this->getContainer()->get('oro_action.test.action_executor');
    }

    public function testExecuteAction()
    {
        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');

        $result = $this->executor->executeAction(
            'format_name',
            [
                'attribute' => null,
                'object' => $user
            ]
        );
        $this->assertEquals('Test User', $result->get('attribute'));
    }

    public function testExecuteActionWithPropertyPath()
    {
        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');

        $result = $this->executor->executeAction(
            'format_name',
            [
                'attribute' => new PropertyPath('test'),
                'object' => $user
            ]
        );
        $this->assertEquals('Test User', $result->get('test'));
    }
}
