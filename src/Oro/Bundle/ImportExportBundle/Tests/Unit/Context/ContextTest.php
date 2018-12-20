<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Oro\Bundle\ImportExportBundle\Context\Context;

class ContextTest extends \PHPUnit\Framework\TestCase
{
    public function testErrors()
    {
        $context = new Context([]);

        $this->assertEmpty($context->getErrors());

        $context->addError('error_1');
        $context->addErrors(['error_2', 'error_3']);

        $this->assertEquals(['error_1', 'error_2', 'error_3'], $context->getErrors());
    }

    public function testPostponedRows()
    {
        $context = new Context([]);

        $this->assertEmpty($context->getPostponedRows());

        $context->addPostponedRow(['header_1' => 'value_1']);
        $context->addPostponedRows([['header_1' => 'value_2'], ['header_1' => 'value_3']]);

        $this->assertEquals([
            ['header_1' => 'value_1'], ['header_1' => 'value_2'], ['header_1' => 'value_3']
        ], $context->getPostponedRows());
    }

    public function testIncrementRead()
    {
        $context = new Context(['incremented_read' => false]);
        $context->incrementReadCount(1);
        self::assertNull($context->getReadCount());

        $context->removeOption('incremented_read');
        $context->incrementReadCount(1);
        self::assertEquals(1, $context->getReadCount());
    }

    public function testExceptions()
    {
        $context = new Context([]);

        $this->assertEmpty($context->getFailureExceptions());

        $exception1 = new \Exception('exception_1');
        $exception2 = new \Exception('exception_2');

        $context->addFailureException($exception1);
        $context->addFailureException($exception2);

        $this->assertEquals(['exception_1', 'exception_2'], $context->getFailureExceptions());
    }

    public function testCount()
    {
        $context = new Context([]);

        $this->assertEquals(0, (int)$context->getReadCount());
        $this->assertEquals(0, (int)$context->getAddCount());
        $this->assertEquals(0, (int)$context->getDeleteCount());
        $this->assertEquals(0, (int)$context->getReplaceCount());
        $this->assertEquals(0, (int)$context->getUpdateCount());
        $this->assertEquals(0, (int)$context->getErrorEntriesCount());

        $context->incrementReadCount();
        $context->incrementAddCount();
        $context->incrementDeleteCount();
        $context->incrementReplaceCount();
        $context->incrementUpdateCount();
        $context->incrementErrorEntriesCount();

        $this->assertEquals(1, (int)$context->getReadCount());
        $this->assertEquals(1, (int)$context->getAddCount());
        $this->assertEquals(1, (int)$context->getDeleteCount());
        $this->assertEquals(1, (int)$context->getReplaceCount());
        $this->assertEquals(1, (int)$context->getUpdateCount());
        $this->assertEquals(1, (int)$context->getErrorEntriesCount());

        $context->incrementReadCount(3);
        $context->incrementAddCount(3);
        $context->incrementDeleteCount(3);
        $context->incrementReplaceCount(3);
        $context->incrementUpdateCount(3);
        $context->incrementErrorEntriesCount(3);

        $this->assertEquals(4, (int)$context->getReadCount());
        $this->assertEquals(4, (int)$context->getAddCount());
        $this->assertEquals(4, (int)$context->getDeleteCount());
        $this->assertEquals(4, (int)$context->getReplaceCount());
        $this->assertEquals(4, (int)$context->getUpdateCount());
        $this->assertEquals(4, (int)$context->getErrorEntriesCount());
    }

    public function testOffset()
    {
        $context = new Context([]);

        $this->assertEquals(0, (int)$context->getReadOffset());

        $context->incrementReadOffset();

        $this->assertEquals(1, (int)$context->getReadOffset());
    }

    /**
     * @dataProvider dataProviderForConfiguration
     *
     * @param array $configuration
     * @param array $getOptionArguments
     * @param bool $hasOption
     * @param bool $value
     */
    public function testConfiguration(array $configuration, array $getOptionArguments, $hasOption, $value)
    {
        $context = new Context($configuration);

        $this->assertEquals($hasOption, $context->hasOption($getOptionArguments[0]));
        $this->assertEquals($value, $context->getOption($getOptionArguments[0], $getOptionArguments[1]));

        $context->removeOption($getOptionArguments[0]);
        $this->assertFalse($context->hasOption($getOptionArguments[0]));
    }

    /**
     * @return array
     */
    public function dataProviderForConfiguration()
    {
        return [
            [[], ['option_1', null], false, null],
            [[], ['option_1', 11], false, 11],
            [['option_1' => 1, 'option_2' => 2], ['option_1', null], true, 1],
            [['option_1' => 1, 'option_2' => 2], ['option_3', null], false, null],
            [['option_1' => 1, 'option_2' => 2], ['option_3', 3], false, 3],
        ];
    }
}
