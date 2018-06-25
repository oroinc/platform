<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult;
use Oro\Bundle\FilterBundle\Expression\Date\Token;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;

class ExpressionResultTest extends \PHPUnit\Framework\TestCase
{
    public function testDateResult()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_DATE, '1990-02-02'));

        $result = $expression->getValue();
        $this->assertFalse($expression->isModifier());
        $this->assertInstanceOf('\DateTime', $result);

        $this->assertSame('1990', $result->format('Y'));
        $this->assertSame('02', $result->format('m'));
        $this->assertSame('02', $result->format('d'));
    }

    public function testTimeResult()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_TIME, '23:00:30'));

        $result = $expression->getValue();
        $this->assertFalse($expression->isModifier());
        $this->assertInstanceOf('\DateTime', $result);

        $this->assertSame('23', $result->format('H'));
        $this->assertSame('00', $result->format('i'));
        $this->assertSame('30', $result->format('s'));
    }

    public function testIntegerResults()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_INTEGER, 3));

        $this->assertTrue($expression->isModifier());
        $expression->add(new ExpressionResult(2));

        $this->assertSame(5, $expression->getValue());

        $expression->subtract(new ExpressionResult(3));

        $this->assertSame(2, $expression->getValue());
    }

    public function testThisDayModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_DAY));
        $result     = $expression->getValue();

        $this->assertInstanceOf('\DateTime', $result);

        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $expectedResult = $dateTime->format('d');
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $dateTime->add(new \DateInterval('P3D'));
        $expectedResult = $dateTime->format('d');
        $expression->add(new ExpressionResult(3));
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $dateTime->sub(new \DateInterval('P8D'));
        $expectedResult = $dateTime->format('d');
        $expression->subtract(new ExpressionResult(8));
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_TODAY));
        $result     = $expression->getValue();

        $this->assertInstanceOf('\DateTime', $result);

        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $expectedResult = $dateTime->format('d');
        $this->assertSame((int)$expectedResult, (int)$result->day);
        $this->assertEquals(0, (int)$result->hour);
        $this->assertEquals(0, (int)$result->minute);
    }

    /**
     * @param ExpressionResult $expression
     * @param int|\DateTime $expected
     *
     * @dataProvider getThisWeekModifications
     */
    public function testThisWeekModify(ExpressionResult $expression, $expected)
    {
        $this->assertExpressionDateSame($expected, $expression);
    }

    public function getThisWeekModifications()
    {
        return [
            'this week' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_THIS_WEEK),
                strtotime('this week'),
            ],
            'this week + 3 weeks' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_THIS_WEEK)
                    ->add(new ExpressionResult(3)),
                strtotime('this week +3 weeks'),
            ],
            'this week + 3 weeks - 8 weeks' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_THIS_WEEK)
                    ->add(new ExpressionResult(3))
                    ->subtract(new ExpressionResult(8)),
                strtotime('this week -5 weeks'),
            ],
        ];
    }

    public function testThisQuarterModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_QUARTER));
        $result     = $expression->getValue();

        $expectedQuarter = (int)ceil(date('m') / 3);
        $this->assertSame($expectedQuarter, (int)$result->quarter);

        $expression->add(new ExpressionResult(1));
        $expectedQuarter += 1;
        if ($expectedQuarter > 4) {
            $expectedQuarter -= 4;
        }
        $this->assertSame($expectedQuarter, (int)$result->quarter);

        $expression->subtract(new ExpressionResult(3));
        $expectedQuarter -= 3;
        if ($expectedQuarter < 1) {
            $expectedQuarter += 4;
        }
        $this->assertSame($expectedQuarter, (int)$result->quarter);
    }

    public function testThisMonthModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_MONTH));
        $result     = $expression->getValue();

        $expectedMonth = (int)date('m');
        $this->assertSame($expectedMonth, (int)$result->month);

        $expression->add(new ExpressionResult(3));
        $expectedMonth += 3;
        if ($expectedMonth > 12) {
            $expectedMonth -= 12;
        }
        $this->assertSame($expectedMonth, (int)$result->month);

        $expression->subtract(new ExpressionResult(2));
        $expectedMonth -= 2;
        if ($expectedMonth < 1) {
            $expectedMonth += 12;
        }
        $this->assertSame($expectedMonth, (int)$result->month);
    }

    public function testThisYearModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_YEAR));
        $result     = $expression->getValue();

        $curYear = (int)date('Y');
        $this->assertSame($curYear, (int)$result->year);

        $expression->add(new ExpressionResult(2));
        $expected = (int)(\DateTime::createFromFormat('U', strtotime('today +2 year'))->format('Y'));
        $this->assertSame($expected, (int)$result->year);

        $expression->subtract(new ExpressionResult(1));
        $expected = (int)(\DateTime::createFromFormat('U', strtotime('today +1 year'))->format('Y'));
        $this->assertSame($expected, (int)$result->year);
    }

    public function testReverseAddition()
    {
        $expression = new ExpressionResult(2);

        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_DAY));
        $expression->add($expressionModify);

        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->add(new \DateInterval('P2D'));
        $expectedResult = $dateTime->format('d');
        $result         = $expression->getValue();
        $this->assertSame((int)$expectedResult, (int)$result->day);
    }

    public function testReverseSubtractionDay()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression       = new ExpressionResult(33);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_DAY));
        $expression->subtract($expressionModify);

        $result      = $expression->getValue();
        $expectedDay = 33 - $dateTime->format('d');
        $this->assertSame($expectedDay, (int)$result);
    }

    public function testReverseSubtractionMonth()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression       = new ExpressionResult(12);
        $expressionModify = new ExpressionResult(
            new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_MONTH)
        );
        $expression->subtract($expressionModify);

        $result        = $expression->getValue();
        $expectedMonth = 12 - (int)$dateTime->format('m');
        $this->assertSame($expectedMonth, (int)$result);
    }

    public function testReverseSubtractionYear()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression       = new ExpressionResult(5000);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_YEAR));
        $expression->subtract($expressionModify);

        $result        = $expression->getValue();
        $expectedMonth = 5000 - (int)$dateTime->format('Y');
        $this->assertSame($expectedMonth, (int)$result);
    }

    public function testReverseSubtractionQuarter()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression       = new ExpressionResult(4);
        $expressionModify = new ExpressionResult(
            new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_QUARTER)
        );
        $expression->subtract($expressionModify);

        $result        = $expression->getValue();
        $expectedMonth = 4 - (int)ceil((int)$dateTime->format('m')/3);
        $this->assertSame($expectedMonth, (int)$result);
    }

    public function testReverseSubtractionWeek()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        // Needed because Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult changes first day of week
        $dateTime->modify('this week');

        $expression       = new ExpressionResult(200);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_WEEK));
        $expression->subtract($expressionModify);

        $result        = $expression->getValue();
        $expectedWeek = 200 - (int)$dateTime->format('W');
        $this->assertSame($expectedWeek, (int)$result);
    }

    /**
     * @param ExpressionResult $expression
     * @param int $time
     *
     * @dataProvider getStartOfOperations
     */
    public function testStartOfOperations(ExpressionResult $expression, $time)
    {
        $this->assertExpressionDateSame($time, $expression);
    }

    public function getStartOfOperations()
    {
        return [
            'start of week' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOW),
                strtotime('monday this week'),
            ],
            'start of week +3 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)
                    ->add(new ExpressionResult(3)),
                strtotime('monday this week +72 hours'),
            ],
            'start of week +3 days -5 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)
                    ->add(new ExpressionResult(3))
                    ->subtract(new ExpressionResult(5)),
                strtotime('monday this week -48 hours'),
            ],
            'start of month' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOM),
                strtotime('first day of this month'),
            ],
            'start of month +3 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)
                    ->add(new ExpressionResult(3)),
                strtotime('first day of this month +72 hours'),
            ],
            'start of month +3 days -5 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)
                    ->add(new ExpressionResult(3))
                    ->subtract(new ExpressionResult(5)),
                strtotime('first day of this month -48 hours'),
            ],
            'start of year' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOY),
                strtotime('first day of january '.date('Y')),
            ],
            'start of year +3 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)
                    ->add(new ExpressionResult(3)),
                strtotime('first day of january '.date('Y').' +72 hours'),
            ],
            'start of year +3 days -5 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)
                    ->add(new ExpressionResult(3))
                    ->subtract(new ExpressionResult(5)),
                strtotime('first day of january '.date('Y').' -48 hours'),
            ],
        ];
    }

    /**
     * @param string $operation
     * @param ExpressionResult $expression
     * @param int|ExpressionResult $expected
     *
     * @dataProvider getStartOfReverseOperations
     */
    public function testStartOfReverseOperations($operation, ExpressionResult $expression, $expected)
    {
        // expression result operations are designed so that the result of (int - expression) is int
        // and (int + expression) is expression
        if ('subtract' === $operation) {
            $this->assertExpressionModifierSame($expected, $expression);
        } else {
            $this->assertExpressionDateSame($expected, $expression);
        }
    }

    public function getStartOfReverseOperations()
    {
        return [
            '33 days - start of week' => [
                'subtract',
                $this->createNumericExpressionResult(33)
                    ->subtract($this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)),
                33 - intval(date_create('monday this week')->format('d'))
            ],
            '3 days + start of week' => [
                'add',
                $this->createNumericExpressionResult(3)
                    ->add($this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)),
                date_create('monday this week')->modify('+3 days')
            ],
            '33 days - start of month' => [
                'subtract',
                $this->createNumericExpressionResult(33)
                    ->subtract($this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)),
                33 - intval(date_create('first day of this month')->format('d'))
            ],
            '3 days + start of month' => [
                'add',
                $this->createNumericExpressionResult(3)
                    ->add($this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)),
                date_create('first day of this month')->modify('+3 days')
            ],
            '33 days - start of year' => [
                'subtract',
                $this->createNumericExpressionResult(33)
                    ->subtract($this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)),
                33 - intval(date_create('first day of january '.date('Y'))->format('d'))
            ],
            '3 days + start of year' => [
                'add',
                $this->createNumericExpressionResult(3)
                    ->add($this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)),
                date_create('first day of january '.date('Y'))->modify(' +3  days')
            ],
        ];
    }

    /**
     * @param int|\DateTime $time
     * @param ExpressionResult $expression
     */
    protected function assertExpressionDateSame($time, ExpressionResult $expression)
    {
        if ($time instanceof \DateTime) {
            $time = $time->getTimestamp();
        }

        $timeYear = (int) date('Y', $time);
        $timeMonth = (int) date('n', $time);
        $timeDay = (int) date('j', $time);
        $timeDate = sprintf("%s-%s-%s", $timeYear, $timeMonth, $timeDay);

        $exprValue = $expression->getValue();
        $exprYear = (int) $exprValue->year;
        $exprMonth = (int) $exprValue->month;
        $exprDay = (int) $exprValue->day;
        $exprDate = sprintf("%s-%s-%s", $exprYear, $exprMonth, $exprDay);

        $this->assertSame($timeDate, $exprDate, sprintf("The current time is %s.", date('r')));
    }

    /**
     * @param int $days
     * @param ExpressionResult $expression
     */
    protected function assertExpressionModifierSame($days, ExpressionResult $expression)
    {
        $this->assertTrue($expression->isModifier(), 'Expression result should be an integer value.');

        $this->assertSame(
            $days,
            $expression->getValue(),
            sprintf(
                "Expression value is '%s'\n The current time is %s.",
                $expression->getValue(),
                date('c')
            )
        );
    }

    /**
     * @param int $value
     *
     * @return ExpressionResult
     */
    protected function createNumericExpressionResult($value)
    {
        return new ExpressionResult($value);
    }

    /**
     * @param int $value One of DateModifierInterface constants
     *
     * @return ExpressionResult
     */
    protected function createVariableExpressionResult($value)
    {
        return new ExpressionResult(new Token(Token::TYPE_VARIABLE, $value));
    }
}
