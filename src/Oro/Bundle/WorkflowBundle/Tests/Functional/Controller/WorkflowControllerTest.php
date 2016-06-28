<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class WorkflowControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testStartTransitionAction()
    {
    }

    public function testTransitionAction()
    {
    }
}
