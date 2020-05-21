<?php
namespace Oro\Bundle\DistributionBundle\Tests\Unit\Console;

use Oro\Bundle\DistributionBundle\Console\Grid;

class GridTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithTwoArguments()
    {
        new Grid(3, [':', '$']);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutSecondArgument()
    {
        new Grid(3);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function shouldBeDefaultDelimiter()
    {
        $row = ['1', '2', '3', '4', '5', '6'];
        $grid = new Grid(\count($row));
        $grid->addRow($row);

        static::assertEquals('1 : 2 : 3 : 4 : 5 : 6', $grid->render());
    }

    /**
     * @test
     */
    public function shouldSetDelimitersFromDefaultDelimiter()
    {
        $row = ['1', '2', '3', '4', '5', '6'];
        $grid = new Grid(\count($row));
        $grid->addRow($row);

        static::assertEquals('1 : 2 : 3 : 4 : 5 : 6', $grid->render());
    }

    /**
     * @test
     */
    public function sizeOfDelimitersShouldBeOneLessThenColumnCount()
    {
        $row = ['1', '2', '3', '4', '5', '6'];
        $grid = new Grid(\count($row));
        $grid->addRow($row);

        static::assertEquals('1 : 2 : 3 : 4 : 5 : 6', $grid->render());
    }

    /**
     * @test
     */
    public function shouldPadDelimitersFromDefaultDelimiter()
    {
        $row = ['1', '2', '3', '4', '5', '6'];
        $grid = new Grid(\count($row), [';', ';', ';']);
        $grid->addRow($row);

        static::assertEquals('1 ; 2 ; 3 ; 4 : 5 : 6', $grid->render());
    }

    /**
     * @test
     */
    public function shouldSliceRedundantDelimiters()
    {
        $row = ['1', '2', '3', '4'];
        $grid = new Grid(\count($row), [';', ';', ';', ';']);
        $grid->addRow($row);

        static::assertEquals('1 ; 2 ; 3 ; 4', $grid->render());
    }

    /**
     * @test
     */
    public function shouldAddRow()
    {
        $grid = new Grid(2);
        static::assertEquals('', $grid->render());

        $grid->addRow([1, 2]);
        static::assertEquals('1 : 2', $grid->render());
    }

    /**
     * @test
     */
    public function shouldSliceRedundantCellsOnAddRow()
    {
        $row = ['1', '2', '3', '4', '5', '6'];
        $grid = new Grid(3);
        $grid->addRow($row);

        static::assertEquals('1 : 2 : 3', $grid->render());
    }

    /**
     * @test
     */
    public function shouldPadRowWithEmptyCellsOnAddRow()
    {
        $row = ['1', '2'];
        $grid = new Grid(4);
        $grid->addRow($row);

        static::assertEquals('1 : 2 :  : ', $grid->render());
    }

    /**
     * @test
     */
    public function shouldWrapDelimiterWithSpacesOnRender()
    {
        $grid = new Grid(3);
        $grid->addRow(['1', '2', '3']);

        static::assertEquals('1 : 2 : 3', $grid->render());
    }

    /**
     * @test
     */
    public function shouldUseDefaultDelimiterIfNotProvidedViaConstructor()
    {
        $expectedResult = <<<GRID
1 : 2 : 3
1 : 2 : 3
1 : 2 : 3
1 : 2 : 3
GRID;
        $grid = new Grid(3);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);
        $expected = preg_replace('/(\r\n)|\n/m', PHP_EOL, $expectedResult);
        $result = $grid->render();
        static::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function shouldUseProvidedDelimiters()
    {
        $expectedResult = <<<GRID
1 ; 2 > 3
1 ; 2 > 3
1 ; 2 > 3
1 ; 2 > 3
GRID;
        $grid = new Grid(3, [';', '>']);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);

        $expected = preg_replace('/(\r\n)|\n/m', PHP_EOL, $expectedResult);
        static::assertEquals($expected, $grid->render());
    }

    /**
     * @test
     */
    public function shouldAlignColumnDataToRight()
    {
        $expectedResult = <<<GRID
 1 :  2 :   3
 1 :  2 :   3
 1 :  2 :   3
10 : 20 : 300
GRID;
        $grid = new Grid(3);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['1', '2', '3']);
        $grid->addRow(['10', '20', '300']);
        $expected = preg_replace('/(\r\n)|\n/m', PHP_EOL, $expectedResult);
        static::assertEquals($expected, $grid->render());
    }

    /**
     * @test
     */
    public function shouldWorkWithOneColumn()
    {
        $expectedResult = <<<GRID
 1
 1
 1
10
GRID;
        $grid = new Grid(1);
        $grid->addRow(['1']);
        $grid->addRow(['1']);
        $grid->addRow(['1']);
        $grid->addRow(['10']);
        $expected = preg_replace('/(\r\n)|\n/m', PHP_EOL, $expectedResult);
        static::assertEquals($expected, $grid->render());
    }
}
