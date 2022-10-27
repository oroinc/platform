<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Carbon\Carbon;
use Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult;
use Oro\Bundle\FilterBundle\Expression\Date\Token;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExpressionResultTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dateProvider
     */
    public function testDateResult(string $date, string $timeZone, string $expected): void
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_DATE, $date), $timeZone);

        $result = $expression->getValue();
        $this->assertFalse($expression->isModifier());
        $this->assertInstanceOf('\DateTime', $result);

        $this->assertSame($expected, $result->format('Y-m-d'));
    }

    public function dateProvider(): \Generator
    {
        yield 'UTC' => [
            'date' => '1990-02-02',
            'timeZone' => 'UTC',
            'expected' => '1990-02-02'
        ];

        yield '(UTC -11:00) Pacific/Niue' => [
            'date' => '1990-02-02',
            'timeZone' => 'Pacific/Niue',
            'expected' => '1990-02-02' // Ignore the time zone, as there is no time.
        ];
    }

    /**
     * @dataProvider timeProvider
     */
    public function testTimeResult(string $time, string $timeZone, string $expected): void
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_TIME, $time, $timeZone));

        $result = $expression->getValue();
        $this->assertFalse($expression->isModifier());
        $this->assertInstanceOf('\DateTime', $result);

        $this->assertSame($expected, $result->format('H:i:s'));
    }

    public function timeProvider(): \Generator
    {
        yield 'UTC' => [
            'time' => '23:00:00',
            'timeZone' => 'UTC',
            'expected' => '23:00:00'
        ];

        yield '(UTC -11:00) Pacific/Niue' => [
            'time' => '23:00:00',
            'timeZone' => 'Pacific/Niue',
            'expected' => '23:00:00' // Ignore the time zone, as there is no date.
        ];
    }

    public function testIntegerResults(): void
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_INTEGER, 3));

        $this->assertTrue($expression->isModifier());

        $expression->add(new ExpressionResult(2));
        $this->assertSame(5, $expression->getValue());

        $expression->subtract(new ExpressionResult(3));
        $this->assertSame(2, $expression->getValue());
    }

    /**
     * @dataProvider thisDayModifyProvider
     *
     * @throws \Exception
     */
    public function testThisDayModify(string $timeZone, int $modifier)
    {
        $token = new Token(Token::TYPE_VARIABLE, $modifier);
        $expression = new ExpressionResult($token, $timeZone);
        $result = $expression->getValue();

        $this->assertInstanceOf('\DateTime', $result);

        $dateTime = new \DateTime('now', new \DateTimeZone($timeZone));
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

        $this->assertSame((int)$expectedResult, (int)$result->day);
        $this->assertEquals(0, (int)$result->hour);
        $this->assertEquals(0, (int)$result->minute);
    }

    public function thisDayModifyProvider(): \Generator
    {
        yield 'UTC and this day' => [
            'timeZone' => 'UTC',
            'modifier' => DateModifierInterface::VAR_THIS_DAY
        ];

        yield 'UTC and today' => [
            'timeZone' => 'UTC',
            'modifier' => DateModifierInterface::VAR_TODAY
        ];

        yield 'UTC and this day(wy)' => [
            'timeZone' => 'UTC',
            'modifier' => DateModifierInterface::VAR_THIS_DAY_W_Y
        ];

        yield '(UTC -11:00) Pacific/Niue and this day' => [
            'timeZone' => 'Pacific/Niue',
            'modifier' => DateModifierInterface::VAR_THIS_DAY
        ];

        yield '(UTC -11:00) Pacific/Niue and today' => [
            'timeZone' => 'Pacific/Niue',
            'modifier' => DateModifierInterface::VAR_TODAY
        ];

        yield '(UTC -11:00) Pacific/Niue and this day(wy)' => [
            'timeZone' => 'Pacific/Niue',
            'modifier' => DateModifierInterface::VAR_THIS_DAY_W_Y
        ];

        yield '(UTC +14:00) Pacific/Kiritimati and this day' => [
            'timeZone' => 'Pacific/Kiritimati',
            'modifier' => DateModifierInterface::VAR_THIS_DAY
        ];

        yield '(UTC +14:00) Pacific/Kiritimati and today' => [
            'timeZone' => 'Pacific/Kiritimati',
            'modifier' => DateModifierInterface::VAR_TODAY
        ];

        yield '(UTC +14:00) Pacific/Kiritimati and this day(wy)' => [
            'timeZone' => 'Pacific/Kiritimati',
            'modifier' => DateModifierInterface::VAR_THIS_DAY_W_Y
        ];
    }

    /**
     * @dataProvider getThisWeekModifications
     */
    public function testThisWeekModify(ExpressionResult $expression, \DateTimeImmutable $expected): void
    {
        /** @var Carbon $exprValue */
        $exprValue = $expression->getValue();
        $this->assertTrue($exprValue->eq($expected), \sprintf(
            'Expected date: %s, actual date: %s',
            $expected->format('c'),
            $exprValue->toIso8601String()
        ));
    }

    /**
     * @throws \Exception
     */
    public function getThisWeekModifications(): \Generator
    {
        yield 'this week with UTC timezone' => [
            $this->createVariableExpressionResult(DateModifierInterface::VAR_THIS_WEEK),
            (new \DateTimeImmutable('this week', new \DateTimeZone('UTC')))->setTime(0, 0),
        ];

        yield 'this week + 3 weeks and Pacific/Niue timezone' => [
            $this->createVariableExpressionResult(DateModifierInterface::VAR_THIS_WEEK, 'Pacific/Niue')
                ->add(new ExpressionResult(3)),
            (new \DateTimeImmutable('this week +3 weeks', new \DateTimeZone('Pacific/Niue')))->setTime(0, 0),
        ];

        yield 'this week + 3 weeks - 8 weeks and Pacific/Kiritimati timezone' => [
            $this->createVariableExpressionResult(DateModifierInterface::VAR_THIS_WEEK, 'Pacific/Kiritimati')
                ->add(new ExpressionResult(3))
                ->subtract(new ExpressionResult(8)),
            (new \DateTimeImmutable('this week -5 weeks', new \DateTimeZone('Pacific/Kiritimati')))->setTime(0, 0),
        ];
    }

    public function testThisQuarterModify(): void
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_QUARTER));
        $result = $expression->getValue();

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

    public function testThisMonthModify(): void
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_MONTH));
        $result = $expression->getValue();

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
        $result = $expression->getValue();

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
        $result = $expression->getValue();
        $this->assertSame((int)$expectedResult, (int)$result->day);
    }

    public function testReverseSubtractionDay()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression = new ExpressionResult(33);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_DAY));
        $expression->subtract($expressionModify);

        $result = $expression->getValue();
        $expectedDay = 33 - $dateTime->format('d');
        $this->assertSame($expectedDay, (int)$result);
    }

    public function testReverseSubtractionMonth()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression = new ExpressionResult(12);
        $expressionModify = new ExpressionResult(
            new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_MONTH)
        );
        $expression->subtract($expressionModify);

        $result = $expression->getValue();
        $expectedMonth = 12 - (int)$dateTime->format('m');
        $this->assertSame($expectedMonth, (int)$result);
    }

    public function testReverseSubtractionYear()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression = new ExpressionResult(5000);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_YEAR));
        $expression->subtract($expressionModify);

        $result = $expression->getValue();
        $expectedMonth = 5000 - (int)$dateTime->format('Y');
        $this->assertSame($expectedMonth, (int)$result);
    }

    public function testReverseSubtractionQuarter()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expression = new ExpressionResult(4);
        $expressionModify = new ExpressionResult(
            new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_QUARTER)
        );
        $expression->subtract($expressionModify);

        $result = $expression->getValue();
        $expectedMonth = 4 - (int)ceil((int)$dateTime->format('m') / 3);
        $this->assertSame($expectedMonth, (int)$result);
    }

    public function testReverseSubtractionWeek()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        // Needed because Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult changes first day of week
        $dateTime->modify('this week');

        $expression = new ExpressionResult(200);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_WEEK));
        $expression->subtract($expressionModify);

        $result = $expression->getValue();
        $expectedWeek = 200 - (int)$dateTime->format('W');
        $this->assertSame($expectedWeek, (int)$result);
    }

    /**
     * @dataProvider getStartOfOperations
     */
    public function testStartOfOperations(ExpressionResult $expression, \DateTimeImmutable $expected)
    {
        /** @var Carbon $exprValue */
        $exprValue = $expression->getValue();
        $this->assertTrue($exprValue->eq($expected), \sprintf(
            'Expected date: %s, actual date: %s',
            $expected->format('c'),
            $exprValue->toIso8601String()
        ));
    }

    public function getStartOfOperations()
    {
        $utc = new \DateTimeZone('UTC');
        return [
            'start of week' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOW),
                (new \DateTimeImmutable('monday this week', $utc))->setTime(0, 0, 0),
            ],
            'start of week +3 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)
                    ->add(new ExpressionResult(3)),
                (new \DateTimeImmutable('monday this week +72 hours', $utc))->setTime(0, 0, 0),
            ],
            'start of week +3 days -5 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)
                    ->add(new ExpressionResult(3))
                    ->subtract(new ExpressionResult(5)),
                (new \DateTimeImmutable('monday this week -48 hours', $utc))->setTime(0, 0, 0),
            ],
            'start of month' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOM),
                (new \DateTimeImmutable('first day of this month', $utc))->setTime(0, 0, 0),
            ],
            'start of month +3 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)
                    ->add(new ExpressionResult(3)),
                (new \DateTimeImmutable('first day of this month +72 hours', $utc))->setTime(0, 0, 0),
            ],
            'start of month +3 days -5 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)
                    ->add(new ExpressionResult(3))
                    ->subtract(new ExpressionResult(5)),
                (new \DateTimeImmutable('first day of this month -48 hours', $utc))->setTime(0, 0, 0),
            ],
            'start of year' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOY),
                (new \DateTimeImmutable('first day of january ' . date('Y'), $utc))->setTime(0, 0, 0),
            ],
            'start of year +3 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)
                    ->add(new ExpressionResult(3)),
                (new \DateTimeImmutable('first day of january ' . date('Y') . ' +72 hours', $utc))->setTime(0, 0, 0),
            ],
            'start of year +3 days -5 days' => [
                $this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)
                    ->add(new ExpressionResult(3))
                    ->subtract(new ExpressionResult(5)),
                (new \DateTimeImmutable('first day of january ' . date('Y') . ' -48 hours', $utc))->setTime(0, 0, 0),
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
            /** @var \DateTimeInterface $expected */
            /** @var Carbon $exprValue */
            $exprValue = $expression->getValue();
            $this->assertTrue($exprValue->eq($expected), \sprintf(
                'Expected date: %s, actual date: %s',
                $expected->format('c'),
                $exprValue->toIso8601String()
            ));
        }
    }

    public function getStartOfReverseOperations()
    {
        $utc = new \DateTimeZone('UTC');
        return [
            '33 days - start of week' => [
                'subtract',
                $this->createNumericExpressionResult(33)
                    ->subtract($this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)),
                33 - intval((new \DateTimeImmutable('monday this week', $utc))->setTime(0, 0, 0)->format('d'))
            ],
            '3 days + start of week' => [
                'add',
                $this->createNumericExpressionResult(3)
                    ->add($this->createVariableExpressionResult(DateModifierInterface::VAR_SOW)),
                (new \DateTimeImmutable('monday this week', $utc))->setTime(0, 0, 0)->modify('+3 days')
            ],
            '33 days - start of month' => [
                'subtract',
                $this->createNumericExpressionResult(33)
                    ->subtract($this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)),
                33 - intval((new \DateTimeImmutable('first day of this month', $utc))->setTime(0, 0, 0)->format('d'))
            ],
            '3 days + start of month' => [
                'add',
                $this->createNumericExpressionResult(3)
                    ->add($this->createVariableExpressionResult(DateModifierInterface::VAR_SOM)),
                (new \DateTimeImmutable('first day of this month', $utc))->setTime(0, 0, 0)->modify('+3 days')
            ],
            '33 days - start of year' => [
                'subtract',
                $this->createNumericExpressionResult(33)
                    ->subtract($this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)),
                33 - intval(
                    (new \DateTimeImmutable('first day of january ' . date('Y'), $utc))->setTime(0, 0, 0)->format('d')
                )
            ],
            '3 days + start of year' => [
                'add',
                $this->createNumericExpressionResult(3)
                    ->add($this->createVariableExpressionResult(DateModifierInterface::VAR_SOY)),
                (new \DateTimeImmutable('first day of january ' . date('Y'), $utc))
                    ->setTime(0, 0, 0)
                    ->modify(' +3  days')
            ],
        ];
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
     * @param string $timeZone
     *
     * @return ExpressionResult
     */
    protected function createVariableExpressionResult(int $value, string $timeZone = 'UTC')
    {
        return new ExpressionResult(new Token(Token::TYPE_VARIABLE, $value), $timeZone);
    }
}
