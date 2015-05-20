<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult;
use Oro\Bundle\FilterBundle\Expression\Date\Token;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

class ExpressionResultTest extends \PHPUnit_Framework_TestCase
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

    public function testThisWeekModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_WEEK));
        $result     = $expression->getValue();

        $expectedResult = date('d', strtotime('this week'));
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expression->add(new ExpressionResult(3));

        $expectedResult = date('d', strtotime('this week +3 weeks'));
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expression->subtract(new ExpressionResult(8));
        $expectedResult = date('d', strtotime('this week -5 weeks'));
        $this->assertSame((int)$expectedResult, (int)$result->day);
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

    public function provider()
    {
        return [
            [DateModifierInterface::VAR_SOW, 'monday this week', '+3 days', '-2 days'],
            [DateModifierInterface::VAR_SOM, 'first day of this month', '+72 hours', '-48 hours'],
            [DateModifierInterface::VAR_SOY, 'first day of january ' . date('Y'), '+72 hours', '-48 hours']
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testStartOfSmthModifier($day, $toTime, $plus3daysSuffix, $minus2daysSuffix)
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, $day));
        $result     = $expression->getValue();

        $expectedResult = date('d', strtotime($toTime));
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expression->add(new ExpressionResult(3));

        $expectedResult = date('d', strtotime($toTime . ' ' . $plus3daysSuffix));
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expression->subtract(new ExpressionResult(5));

        $expectedResult = date('d', strtotime($toTime . ' ' . $minus2daysSuffix));
        $this->assertSame((int)$expectedResult, (int)$result->day);
    }

    /**
     * @dataProvider provider
     */
    public function testReverseStartOfSmthModifier($day, $toTime)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, $day));

        $expression = new ExpressionResult(33);
        $expression->subtract($expressionModify);

        $result      = $expression->getValue();
        $expectedDay = 33 - date('d', strtotime($toTime));

        $this->assertSame($expectedDay, (int) $result);

        $expression = new ExpressionResult(1);
        $expression->add($expressionModify);

        $result      = $expression->getValue();
        $expectedDay = 1 + date('d', strtotime($toTime));

        $this->assertSame($expectedDay, $result->day);
    }
}
