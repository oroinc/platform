<?php
namespace Oro\Bundle\DistributionBundle\Tests\Unit\Console;

use Oro\Bundle\DistributionBundle\Console\Grid;

class GridTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithTwoArguments()
    {
        new Grid(3, [':', '$']);
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutSecondArgument()
    {
        new Grid(3);
    }

    /**
     * @test
     */
    public function shouldBeDefaultDelimiter()
    {
        $grid = new Grid(2);

        $this->assertAttributeEquals(':', 'defaultDelimiter', $grid);
    }

    /**
     * @test
     */
    public function shouldSetDelimitersFromDefaultDelimiter()
    {
        $grid = new Grid(6);

        $this->assertAttributeEquals([':', ':', ':', ':', ':'], 'delimiters', $grid);
    }

    /**
     * @test
     */
    public function sizeOfDelimitersShouldBeOneLessThenColumnCount()
    {
        $grid = new Grid(6);

        $this->assertAttributeCount(5, 'delimiters', $grid);
    }

    /**
     * @test
     */
    public function shouldPadDelimitersFromDefaultDelimiter()
    {
        $grid = new Grid(6, [';', ';', ';']);

        $this->assertAttributeEquals([';', ';', ';', ':', ':'], 'delimiters', $grid);
    }

    /**
     * @test
     */
    public function shouldSliceRedundantDelimiters()
    {
        $grid = new Grid(4, [';', ';', ';', ';']);

        $this->assertAttributeEquals([';', ';', ';'], 'delimiters', $grid);
    }

    /**
     * @test
     */
    public function shouldAddRow()
    {
        $grid = new Grid(2);
        $this->assertAttributeCount(0, 'rows', $grid);

        $grid->addRow([1, 2]);
        $this->assertAttributeCount(1, 'rows', $grid);
    }

    /**
     * @test
     */
    public function shouldSliceRedundantCellsOnAddRow()
    {
        $grid = new Grid(3);
        $grid->addRow(['1', '2', '3', '4', '5']);

        $this->assertAttributeEquals([['1', '2', '3']], 'rows', $grid);
    }

    /**
     * @test
     */
    public function shouldPadRowWithEmptyCellsOnAddRow()
    {
        $grid = new Grid(4);
        $grid->addRow(['1', '2']);

        $this->assertAttributeEquals([['1', '2', '', '']], 'rows', $grid);
    }

    /**
     * @test
     */
    public function shouldWrapDelimiterWithSpacesOnRender()
    {
        $expectedResult = '1 : 2 : 3';
        $grid = new Grid(3);
        $grid->addRow(['1', '2', '3']);

        $this->assertEquals($expectedResult, $grid->render());
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

        $this->assertEquals($expectedResult, $grid->render());
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

        $this->assertEquals($expectedResult, $grid->render());
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

        $this->assertEquals($expectedResult, $grid->render());
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

        $this->assertEquals($expectedResult, $grid->render());
    }
}
