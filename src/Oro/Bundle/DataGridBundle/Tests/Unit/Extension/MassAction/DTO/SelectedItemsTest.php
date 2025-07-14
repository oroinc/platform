<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\DTO;

use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use PHPUnit\Framework\TestCase;

class SelectedItemsTest extends TestCase
{
    public function testIsEmptyWhenEmpty(): void
    {
        $selectedItems = new SelectedItems([], true);

        self::assertTrue($selectedItems->isEmpty());
    }

    /**
     * @dataProvider notEmptyDataProvider
     */
    public function testIsEmptyWhenNotEmpty(array $values, bool $inset): void
    {
        $selectedItems = new SelectedItems($values, $inset);

        self::assertFalse($selectedItems->isEmpty());
    }

    public function notEmptyDataProvider(): array
    {
        return [
            'inset is true and some values given' => [
                'values' => [2, 5],
                'inset' => true
            ],
            'inset is false and no values given' => [
                'values' => [],
                'inset' => false
            ],
            'inset is false and some values given' => [
                'values' => [5, 7],
                'inset' => false
            ],
        ];
    }

    public function testGetValues(): void
    {
        $values = [1, 2, 3];
        $selectedItems = new SelectedItems($values, true);

        self::assertEquals($values, $selectedItems->getValues());
    }

    public function testIsInsetWhenTrue(): void
    {
        $selectedItems = new SelectedItems([], true);

        self::assertTrue($selectedItems->isInset());
    }

    public function testIsInsetWhenFalse(): void
    {
        $selectedItems = new SelectedItems([], false);

        self::assertFalse($selectedItems->isInset());
    }

    public function testCreateFromParametersWithDefaultValues(): void
    {
        $selectedItems = SelectedItems::createFromParameters([]);

        self::assertEquals([], $selectedItems->getValues());
        self::assertEquals(true, $selectedItems->isInset());
    }

    public function testCreateFromParametersWithInsetGiven(): void
    {
        $expectedSelectedItems = new SelectedItems([], false);

        self::assertEquals($expectedSelectedItems, SelectedItems::createFromParameters(['inset' => false]));
    }

    public function testCreateFromParametersWithValuesGiven(): void
    {
        $values = [2, 9];
        $expectedSelectedItems = new SelectedItems($values, true);

        self::assertEquals($expectedSelectedItems, SelectedItems::createFromParameters(['values' => $values]));
    }

    public function testCreateFromParameters(): void
    {
        $values = [2, 9];
        $inset = false;
        $expectedSelectedItems = new SelectedItems($values, $inset);

        self::assertEquals(
            $expectedSelectedItems,
            SelectedItems::createFromParameters(['values' => $values, 'inset' => $inset])
        );
    }
}
