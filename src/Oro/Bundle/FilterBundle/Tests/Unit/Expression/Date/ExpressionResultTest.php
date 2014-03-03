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

        $expectedResult = date('d');
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expectedResult = date('d', strtotime('today +3 days'));
        $expression->add(new ExpressionResult(3));
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expectedResult = date('d', strtotime('today -5 days'));
        $expression->subtract(new ExpressionResult(8));
        $this->assertSame((int)$expectedResult, (int)$result->day);
    }

    public function testThisWeekModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_WEEK));
        $result     = $expression->getValue();

        $expectedResult = \DateTime::createFromFormat('U', strtotime('this week'))->format('d');
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expression->add(new ExpressionResult(3));
        $expectedResult = \DateTime::createFromFormat('U', strtotime('this week +3 weeks'))->format('d');
        $this->assertSame((int)$expectedResult, (int)$result->day);

        $expression->subtract(new ExpressionResult(8));
        $expectedResult = \DateTime::createFromFormat('U', strtotime('this week -5 weeks'))->format('d');
        $this->assertSame((int)$expectedResult, (int)$result->day);
    }

    public function testThisQuarterModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_QUARTER));
        $result     = $expression->getValue();

        $curMonth   = date('m');
        $curQuarter = (int)ceil($curMonth / 3);

        $this->assertSame($curQuarter, (int)$result->quarter);

        $expression->add(new ExpressionResult(1));
        $expected = (int)ceil((\DateTime::createFromFormat('U', strtotime('today +3 month'))->format('m')) / 3);
        $this->assertSame($expected, (int)$result->quarter);

        $expression->subtract(new ExpressionResult(3));
        $expected = (int)ceil((\DateTime::createFromFormat('U', strtotime('today -6 month'))->format('m')) / 3);
        $this->assertSame($expected, (int)$result->quarter);
    }

    public function testThisMonthModify()
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_MONTH));
        $result     = $expression->getValue();

        $curMonth = (int)date('m');
        $this->assertSame($curMonth, (int)$result->month);

        $expression->add(new ExpressionResult(3));
        $expected = (int)(\DateTime::createFromFormat('U', strtotime('today +3 month'))->format('m'));
        $this->assertSame($expected, (int)$result->month);

        $expression->subtract(new ExpressionResult(2));
        $expected = (int)(\DateTime::createFromFormat('U', strtotime('today +1 month'))->format('m'));
        $this->assertSame($expected, (int)$result->month);
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

    /**
     * @dataProvider variableProvider
     * @expectedException \Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException
     *
     * @param int    $variable
     * @param string $operation
     */
    public function testDeniedVariables($variable, $operation)
    {
        $expression = new ExpressionResult(new Token(Token::TYPE_VARIABLE, $variable));

        $expression->{$operation}(new ExpressionResult(1));
    }

    /**
     * @dataProvider variableProvider
     * @expectedException \Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException
     *
     * @param int    $variable
     * @param string $operation
     */
    public function testDeniedReverseVariables($variable, $operation)
    {
        $expression       = new ExpressionResult(123);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, $variable));

        $expression->{$operation}($expressionModify);
    }

    public function variableProvider()
    {
        return [
            [DateModifierInterface::VAR_SOM, 'add'],
            [DateModifierInterface::VAR_SOM, 'subtract'],
            [DateModifierInterface::VAR_SOQ, 'add'],
            [DateModifierInterface::VAR_SOQ, 'subtract'],
            [DateModifierInterface::VAR_SOY, 'add'],
            [DateModifierInterface::VAR_SOY, 'subtract'],
        ];
    }

    public function testReverseAddition()
    {
        $expression = new ExpressionResult(2);

        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_DAY));
        $expression->add($expressionModify);

        $expectedResult = date('d', strtotime('today +2 days'));
        $result         = $expression->getValue();
        $this->assertSame((int)$expectedResult, (int)$result->day);
    }

    public function testReverseSubtraction()
    {
        $expression       = new ExpressionResult(33);
        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_DAY));
        $expression->subtract($expressionModify);

        $day    = date('d');
        $result = $expression->getValue();
        $this->assertSame(33 - (int)$day, (int)$result);

        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_YEAR));
        $expression       = new ExpressionResult(5000);
        $expression->subtract($expressionModify);

        $year   = date('Y');
        $result = $expression->getValue();
        $this->assertSame(5000 - (int)$year, (int)$result);

        $expressionModify
                    = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_MONTH));
        $expression = new ExpressionResult(12);
        $expression->subtract($expressionModify);

        $month  = date('m');
        $result = $expression->getValue();
        $this->assertSame(12 - (int)$month, (int)$result);

        $expressionModify
                    = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_QUARTER));
        $expression = new ExpressionResult(4);
        $expression->subtract($expressionModify);

        $curMonth   = date('m');
        $curQuarter = (int)ceil($curMonth / 3);
        $result     = $expression->getValue();
        $this->assertSame(4 - (int)$curQuarter, (int)$result);

        $expressionModify = new ExpressionResult(new Token(Token::TYPE_VARIABLE, DateModifierInterface::VAR_THIS_WEEK));
        $expression       = new ExpressionResult(200);
        $expression->subtract($expressionModify);
        $expectedResult = date('W');
        $result         = $expression->getValue();
        $this->assertSame(200 - (int)$expectedResult, (int)$result);
    }
}
