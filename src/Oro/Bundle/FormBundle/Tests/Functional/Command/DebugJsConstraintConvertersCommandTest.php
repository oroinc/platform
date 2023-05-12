<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Functional\Command;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\Converters\GenericConstraintConverter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class DebugJsConstraintConvertersCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testExecute(): void
    {
        $commandTester = $this->doExecuteCommand('oro:debug:form:js-constraint-converters');
        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, GenericConstraintConverter::class);
    }
}
