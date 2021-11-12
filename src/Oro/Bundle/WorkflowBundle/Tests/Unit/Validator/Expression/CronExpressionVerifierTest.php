<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Validator\Expression;

use Oro\Bundle\WorkflowBundle\Validator\Expression\CronExpressionVerifier;
use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

class CronExpressionVerifierTest extends \PHPUnit\Framework\TestCase
{
    /** @var CronExpressionVerifier */
    private $verifier;

    protected function setUp(): void
    {
        $this->verifier = new CronExpressionVerifier();
    }

    /**
     * @dataProvider validateExpressionDataProvider
     */
    public function testExpression(string $expression, \Exception $exception = null)
    {
        if ($exception) {
            $this->expectException(get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
        }

        $this->assertTrue($this->verifier->verify($expression));
    }

    public function validateExpressionDataProvider(): array
    {
        return [
            ['expression' => '@yearly', 'exception' => null],
            ['expression' => '@annually', 'exception' => null],
            ['expression' => '@monthly', 'exception' => null],
            ['expression' => '@weekly', 'exception' => null],
            ['expression' => '@daily', 'exception' => null],
            ['expression' => '@hourly', 'exception' => null],
            ['expression' => '0 0 1 1 *', 'exception' => null],
            [
                'expression' => 'NON CRON EXPRESSION',
                'exception' => new ExpressionException('NON CRON EXPRESSION is not a valid CRON expression')
            ],
        ];
    }
}
