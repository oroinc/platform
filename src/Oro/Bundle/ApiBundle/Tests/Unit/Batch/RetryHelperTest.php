<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus as S;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Batch\RetryHelper;

class RetryHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var RetryHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new RetryHelper();
    }

    public function testHasItemErrorsToSaveWhenItemDoesNotHaveErrors()
    {
        $hasItemsToRetry = false;
        $processedItemStatuses = [S::HAS_ERRORS];

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(false);

        self::assertFalse($this->helper->hasItemErrorsToSave($item, $hasItemsToRetry, $processedItemStatuses));
    }

    public function testHasItemErrorsToSaveWhenItemHasErrorsAndNoItemsToRetry()
    {
        $hasItemsToRetry = false;
        $processedItemStatuses = [S::HAS_ERRORS];

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(true);

        self::assertTrue($this->helper->hasItemErrorsToSave($item, $hasItemsToRetry, $processedItemStatuses));
    }

    public function testHasItemErrorsToSaveWhenItemHasErrorsAndHasItemsToRetryAndItemDoesNotHavePermanentErrors()
    {
        $hasItemsToRetry = true;
        $processedItemStatuses = [S::HAS_ERRORS];

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(true);

        self::assertFalse($this->helper->hasItemErrorsToSave($item, $hasItemsToRetry, $processedItemStatuses));
    }

    public function testHasItemErrorsToSaveWhenItemHasErrorsAndHasItemsToRetryAndItemHasPermanentErrors()
    {
        $hasItemsToRetry = true;
        $processedItemStatuses = [S::HAS_PERMANENT_ERRORS];

        $item = $this->createMock(BatchUpdateItem::class);
        $itemContext = $this->createMock(BatchUpdateItemContext::class);
        $item->expects(self::once())
            ->method('getContext')
            ->willReturn($itemContext);
        $itemContext->expects(self::once())
            ->method('hasErrors')
            ->willReturn(true);

        self::assertTrue($this->helper->hasItemErrorsToSave($item, $hasItemsToRetry, $processedItemStatuses));
    }

    /**
     * @dataProvider hasItemsToRetryDataProvider
     */
    public function testHasItemsToRetry(array $rawItems, array $processedItemStatuses, bool $expectedResult)
    {
        self::assertSame(
            $expectedResult,
            $this->helper->hasItemsToRetry($rawItems, $processedItemStatuses)
        );
    }

    public function hasItemsToRetryDataProvider(): array
    {
        return [
            'NOT_PROCESSED'                             => [
                [['key' => '1']],
                [S::NOT_PROCESSED],
                false
            ],
            'NO_ERRORS'                                 => [
                [['key' => '1']],
                [S::NO_ERRORS],
                false
            ],
            'HAS_ERRORS'                                => [
                [['key' => '1']],
                [S::HAS_ERRORS],
                false
            ],
            'HAS_PERMANENT_ERRORS'                      => [
                [['key' => '1']],
                [S::HAS_PERMANENT_ERRORS],
                false
            ],
            'NOT_PROCESSED,NOT_PROCESSED'               => [
                [['key' => '1'], ['key' => '2']],
                [S::NOT_PROCESSED, S::NOT_PROCESSED],
                true
            ],
            'NO_ERRORS,NO_ERRORS'                       => [
                [['key' => '1'], ['key' => '2']],
                [S::NO_ERRORS, S::NO_ERRORS],
                false
            ],
            'NOT_PROCESSED,HAS_ERRORS'                  => [
                [['key' => '1'], ['key' => '2']],
                [S::NOT_PROCESSED, S::HAS_ERRORS],
                true
            ],
            'NO_ERRORS,HAS_ERRORS'                      => [
                [['key' => '1'], ['key' => '2']],
                [S::NO_ERRORS, S::HAS_ERRORS],
                true
            ],
            'NOT_PROCESSED,HAS_PERMANENT_ERRORS'        => [
                [['key' => '1'], ['key' => '2']],
                [S::NOT_PROCESSED, S::HAS_PERMANENT_ERRORS],
                true
            ],
            'NO_ERRORS,HAS_PERMANENT_ERRORS'            => [
                [['key' => '1'], ['key' => '2']],
                [S::NO_ERRORS, S::HAS_PERMANENT_ERRORS],
                false
            ],
            'HAS_ERRORS,HAS_ERRORS'                     => [
                [['key' => '1'], ['key' => '2']],
                [S::HAS_ERRORS, S::HAS_ERRORS],
                true
            ],
            'HAS_ERRORS,HAS_PERMANENT_ERRORS'           => [
                [['key' => '1'], ['key' => '2']],
                [S::HAS_ERRORS, S::HAS_PERMANENT_ERRORS],
                true
            ],
            'HAS_PERMANENT_ERRORS,HAS_PERMANENT_ERRORS' => [
                [['key' => '1'], ['key' => '2']],
                [S::HAS_PERMANENT_ERRORS, S::HAS_PERMANENT_ERRORS],
                false
            ]
        ];
    }

    /**
     * @dataProvider getChunksToRetryDataProvider
     */
    public function testGetChunksToRetry(array $rawItems, array $processedItemStatuses, array $expectedChunks)
    {
        self::assertEquals(
            $expectedChunks,
            $this->helper->getChunksToRetry($rawItems, $processedItemStatuses)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getChunksToRetryDataProvider(): array
    {
        return [
            'NOT_PROCESSED'                                                         => [
                [['key' => '1']],
                [S::NOT_PROCESSED],
                [
                    [0, [['key' => '1']]]
                ]
            ],
            'NO_ERRORS'                                                             => [
                [['key' => '1']],
                [S::NO_ERRORS],
                []
            ],
            'HAS_ERRORS'                                                            => [
                [['key' => '1']],
                [S::HAS_ERRORS],
                [
                    [0, [['key' => '1']]]
                ]
            ],
            'HAS_PERMANENT_ERRORS'                                                  => [
                [['key' => '1']],
                [S::HAS_PERMANENT_ERRORS],
                []
            ],
            'NOT_PROCESSED,NOT_PROCESSED'                                           => [
                [['key' => '1'], ['key' => '2']],
                [S::NOT_PROCESSED, S::NOT_PROCESSED],
                [
                    [0, [['key' => '1'], ['key' => '2']]]
                ]
            ],
            'NO_ERRORS,NO_ERRORS'                                                   => [
                [['key' => '1'], ['key' => '2']],
                [S::NO_ERRORS, S::NO_ERRORS],
                []
            ],
            'HAS_ERRORS,HAS_ERRORS'                                                 => [
                [['key' => '1'], ['key' => '2']],
                [S::HAS_ERRORS, S::HAS_ERRORS],
                [
                    [0, [['key' => '1']]],
                    [1, [['key' => '2']]]
                ]
            ],
            'NOT_PROCESSED,HAS_ERRORS,NOT_PROCESSED'                                => [
                [['key' => '1'], ['key' => '2'], ['key' => '3']],
                [S::NOT_PROCESSED, S::HAS_ERRORS, S::NOT_PROCESSED],
                [
                    [0, [['key' => '1']]],
                    [1, [['key' => '2']]],
                    [2, [['key' => '3']]]
                ]
            ],
            'NO_ERRORS,HAS_ERRORS,NO_ERRORS'                                        => [
                [['key' => '1'], ['key' => '2'], ['key' => '3']],
                [S::NO_ERRORS, S::HAS_ERRORS, S::NO_ERRORS],
                [
                    [1, [['key' => '2']]]
                ]
            ],
            'HAS_ERRORS,NOT_PROCESSED,HAS_ERRORS'                                   => [
                [['key' => '1'], ['key' => '2'], ['key' => '3']],
                [S::HAS_ERRORS, S::NOT_PROCESSED, S::HAS_ERRORS],
                [
                    [0, [['key' => '1']]],
                    [1, [['key' => '2']]],
                    [2, [['key' => '3']]]
                ]
            ],
            'HAS_ERRORS,NO_ERRORS,HAS_ERRORS'                                       => [
                [['key' => '1'], ['key' => '2'], ['key' => '3']],
                [S::HAS_ERRORS, S::NO_ERRORS, S::HAS_ERRORS],
                [
                    [0, [['key' => '1']]],
                    [2, [['key' => '3']]]
                ]
            ],
            'NOT_PROCESSED,HAS_ERRORS,HAS_ERRORS,NOT_PROCESSED,NOT_PROCESSED'       => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4'], ['key' => '5']],
                [S::NOT_PROCESSED, S::HAS_ERRORS, S::HAS_ERRORS, S::NOT_PROCESSED, S::NOT_PROCESSED],
                [
                    [0, [['key' => '1']]],
                    [1, [['key' => '2']]],
                    [2, [['key' => '3']]],
                    [3, [['key' => '4'], ['key' => '5']]]
                ]
            ],
            'NO_ERRORS,HAS_ERRORS,HAS_ERRORS,NO_ERRORS,NO_ERRORS'                   => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4'], ['key' => '5']],
                [S::NO_ERRORS, S::HAS_ERRORS, S::HAS_ERRORS, S::NO_ERRORS, S::NO_ERRORS],
                [
                    [1, [['key' => '2']]],
                    [2, [['key' => '3']]]
                ]
            ],
            'HAS_ERRORS,NOT_PROCESSED,NOT_PROCESSED,HAS_ERRORS,HAS_ERRORS'          => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4'], ['key' => '5']],
                [S::HAS_ERRORS, S::NOT_PROCESSED, S::NOT_PROCESSED, S::HAS_ERRORS, S::HAS_ERRORS],
                [
                    [0, [['key' => '1']]],
                    [1, [['key' => '2'], ['key' => '3']]],
                    [3, [['key' => '4']]],
                    [4, [['key' => '5']]]
                ]
            ],
            'HAS_ERRORS,NO_ERRORS,NO_ERRORS,HAS_ERRORS,HAS_ERRORS'                  => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4'], ['key' => '5']],
                [S::HAS_ERRORS, S::NO_ERRORS, S::NO_ERRORS, S::HAS_ERRORS, S::HAS_ERRORS],
                [
                    [0, [['key' => '1']]],
                    [3, [['key' => '4']]],
                    [4, [['key' => '5']]]
                ]
            ],
            'HAS_ERRORS,HAS_ERRORS,HAS_ERRORS,NOT_PROCESSED'                        => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::HAS_ERRORS, S::HAS_ERRORS, S::HAS_ERRORS, S::NOT_PROCESSED],
                [
                    [0, [['key' => '1']]],
                    [1, [['key' => '2']]],
                    [2, [['key' => '3']]],
                    [3, [['key' => '4']]]
                ]
            ],
            'HAS_ERRORS,HAS_ERRORS,HAS_ERRORS,NO_ERRORS'                            => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::HAS_ERRORS, S::HAS_ERRORS, S::HAS_ERRORS, S::NO_ERRORS],
                [
                    [0, [['key' => '1']]],
                    [1, [['key' => '2']]],
                    [2, [['key' => '3']]]
                ]
            ],
            'NOT_PROCESSED,NOT_PROCESSED,NOT_PROCESSED,HAS_ERRORS'                  => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::NOT_PROCESSED, S::NOT_PROCESSED, S::NOT_PROCESSED, S::HAS_ERRORS],
                [
                    [0, [['key' => '1'], ['key' => '2'], ['key' => '3']]],
                    [3, [['key' => '4']]]
                ]
            ],
            'NO_ERRORS,NO_ERRORS,NO_ERRORS,HAS_ERRORS'                              => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::NO_ERRORS, S::NO_ERRORS, S::NO_ERRORS, S::HAS_ERRORS],
                [
                    [3, [['key' => '4']]]
                ]
            ],
            'NOT_PROCESSED,HAS_PERMANENT_ERRORS,HAS_PERMANENT_ERRORS,NOT_PROCESSED' => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::NOT_PROCESSED, S::HAS_PERMANENT_ERRORS, S::HAS_PERMANENT_ERRORS, S::NOT_PROCESSED],
                [
                    [0, [['key' => '1']]],
                    [3, [['key' => '4']]]
                ]
            ],
            'NO_ERRORS,HAS_PERMANENT_ERRORS,HAS_PERMANENT_ERRORS,NO_ERRORS'         => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::NO_ERRORS, S::HAS_PERMANENT_ERRORS, S::HAS_PERMANENT_ERRORS, S::NO_ERRORS],
                []
            ],
            'HAS_PERMANENT_ERRORS,NOT_PROCESSED,NOT_PROCESSED,HAS_PERMANENT_ERRORS' => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::HAS_PERMANENT_ERRORS, S::NOT_PROCESSED, S::NOT_PROCESSED, S::HAS_PERMANENT_ERRORS],
                [
                    [1, [['key' => '2'], ['key' => '3']]]
                ]
            ],
            'HAS_PERMANENT_ERRORS,NO_ERRORS,NO_ERRORS,HAS_PERMANENT_ERRORS'         => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::HAS_PERMANENT_ERRORS, S::NO_ERRORS, S::NO_ERRORS, S::HAS_PERMANENT_ERRORS],
                []
            ],
            'HAS_ERRORS,HAS_PERMANENT_ERRORS,HAS_PERMANENT_ERRORS,HAS_ERRORS'       => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::HAS_ERRORS, S::HAS_PERMANENT_ERRORS, S::HAS_PERMANENT_ERRORS, S::HAS_ERRORS],
                [
                    [0, [['key' => '1']]],
                    [3, [['key' => '4']]]
                ]
            ],
            'HAS_PERMANENT_ERRORS,HAS_ERRORS,HAS_ERRORS,HAS_PERMANENT_ERRORS'       => [
                [['key' => '1'], ['key' => '2'], ['key' => '3'], ['key' => '4']],
                [S::HAS_PERMANENT_ERRORS, S::HAS_ERRORS, S::HAS_ERRORS, S::HAS_PERMANENT_ERRORS],
                [
                    [1, [['key' => '2']]],
                    [2, [['key' => '3']]]
                ]
            ]
        ];
    }
}
