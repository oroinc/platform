<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Validator\Expression;

use Oro\Bundle\WorkflowBundle\Validator\Expression\CronExpressionVerifier;
use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

class CronExpressionVerifierTest extends \PHPUnit_Framework_TestCase
{
    /** @var CronExpressionVerifier */
    protected $verifier;

    public function setUp()
    {
        $this->verifier = new CronExpressionVerifier();
    }

    public function tearDown()
    {
        unset($this->verifier);
    }

    /**
     * @param string $expression
     * @param \Exception|null $exception
     *
     * @dataProvider validateExpressionDataProvider
     */
    public function testExpression($expression, \Exception $exception = null)
    {
        if ($exception) {
            $this->setExpectedException(get_class($exception), $exception->getMessage());
        }

        $this->assertTrue($this->verifier->verify($expression));
    }

    /**
     * @return array
     */
    public function validateExpressionDataProvider()
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
