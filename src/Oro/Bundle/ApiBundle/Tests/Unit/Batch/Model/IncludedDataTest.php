<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorInterface;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\JsonApiIncludeAccessor;
use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;

class IncludedDataTest extends \PHPUnit\Framework\TestCase
{
    /** @var ItemKeyBuilder */
    private $itemKeyBuilder;

    /** @var IncludeAccessorInterface */
    private $includeAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileLockManager */
    private $fileLockManager;

    protected function setUp(): void
    {
        $this->itemKeyBuilder = new ItemKeyBuilder();
        $this->includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $this->fileLockManager = $this->createMock(FileLockManager::class);
    }

    private function getIncludedData(?array $lockFileNames, array $items, array $processedItems): IncludedData
    {
        return new IncludedData(
            $this->itemKeyBuilder,
            $this->includeAccessor,
            $this->fileLockManager,
            $lockFileNames,
            $items,
            $processedItems
        );
    }

    public function testGetIncludeAccessor()
    {
        $items = [
            [['type' => 'accounts', 'id' => '1'], 10, 'included']
        ];
        $includedData = $this->getIncludedData(['test.lock'], $items, []);
        self::assertSame($this->includeAccessor, $includedData->getIncludeAccessor());
    }

    public function testGetIncludedItem()
    {
        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included'],
            'contacts|2' => [['type' => 'contacts', 'id' => '2'], 11, 'included']
        ];
        $includedData = $this->getIncludedData(['test.lock'], $items, []);
        self::assertSame(['type' => 'accounts', 'id' => '1'], $includedData->getIncludedItem(10));
        self::assertSame(['type' => 'contacts', 'id' => '2'], $includedData->getIncludedItem(11));
        self::assertNull($includedData->getIncludedItem(12));
        self::assertNull($includedData->getIncludedItem(999));
    }

    public function testGetIncludedItemIndex()
    {
        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included'],
            'contacts|2' => [['type' => 'contacts', 'id' => '2'], 11, 'included']
        ];
        $includedData = $this->getIncludedData(['test.lock'], $items, []);
        self::assertSame(10, $includedData->getIncludedItemIndex('accounts', '1'));
        self::assertSame(11, $includedData->getIncludedItemIndex('contacts', '2'));
        self::assertNull($includedData->getIncludedItemIndex('accounts', '2'));
    }

    public function testGetIncludedItemSectionName()
    {
        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included'],
            'contacts|2' => [['type' => 'contacts', 'id' => '2'], 11, 'included']
        ];
        $includedData = $this->getIncludedData(['test.lock'], $items, []);
        self::assertSame('included', $includedData->getIncludedItemSectionName('accounts', '1'));
        self::assertSame('included', $includedData->getIncludedItemSectionName('contacts', '2'));
        self::assertNull($includedData->getIncludedItemSectionName('accounts', '2'));
    }

    public function testGetAllSectionNames()
    {
        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included'],
            'contacts|2' => [['type' => 'contacts', 'id' => '2'], 11, 'included']
        ];
        $includedData = $this->getIncludedData(['test.lock'], $items, []);
        self::assertSame(['included'], $includedData->getAllSectionNames());
    }

    public function testHasProcessedIncludedItemsWhenProcessedIncludedItemsExist()
    {
        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included']
        ];
        $processedItems = [
            'accounts|2' => 12
        ];
        $includedData = $this->getIncludedData(['test.lock'], $items, $processedItems);
        self::assertTrue($includedData->hasProcessedIncludedItems());
    }

    public function testHasProcessedIncludedItemsWhenProcessedIncludedItemsDoNotExist()
    {
        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included']
        ];
        $processedItems = [];
        $includedData = $this->getIncludedData(['test.lock'], $items, $processedItems);
        self::assertFalse($includedData->hasProcessedIncludedItems());
    }

    public function testGetProcessedIncludedItemId()
    {
        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included'],
            'contacts|2' => [['type' => 'contacts', 'id' => '2'], 11, 'included']
        ];
        $processedItems = [
            'accounts|2' => 12,
            'contacts|1' => 21,
        ];
        $includedData = $this->getIncludedData(['test.lock'], $items, $processedItems);
        self::assertTrue($includedData->hasProcessedIncludedItems());
        self::assertSame(12, $includedData->getProcessedIncludedItemId('accounts', '2'));
        self::assertSame(21, $includedData->getProcessedIncludedItemId('contacts', '1'));
        self::assertNull($includedData->getProcessedIncludedItemId('accounts', '1'));
        self::assertNull($includedData->getProcessedIncludedItemId('contacts', '2'));
        self::assertNull($includedData->getProcessedIncludedItemId('accounts', '3'));
    }

    public function testUnlock()
    {
        $this->fileLockManager->expects(self::exactly(2))
            ->method('releaseLock')
            ->withConsecutive(['test1.lock'], ['test2.lock']);

        $items = [
            'accounts|1' => [['type' => 'accounts', 'id' => '1'], 10, 'included']
        ];
        $includedData = $this->getIncludedData(['test1.lock', 'test2.lock'], $items, []);
        $includedData->unlock();
        // test that the second call of unlock() do not cause the unlock of the already unlocked include index
        $includedData->unlock();
    }

    public function testUnlockWhenIncludeIndexWasNotLocked()
    {
        $this->fileLockManager->expects(self::never())
            ->method('releaseLock');

        $includedData = $this->getIncludedData(null, [], []);
        $includedData->unlock();
    }
}
