<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Query\FilterProcessorContext;
use PHPUnit\Framework\TestCase;

class FilterProcessorContextTest extends TestCase
{
    private FilterProcessorContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new FilterProcessorContext();
    }

    public function testShouldBePossibleToInitWithoutColumns(): void
    {
        $entity = 'Test\Entity';
        $definition = ['key' => 'value'];
        $source = new QueryDesigner($entity, QueryDefinitionUtil::encodeDefinition($definition));

        $this->context->init($source);

        self::assertSame($entity, $this->context->getRootEntity());
        self::assertSame($definition, $this->context->getDefinition());
    }

    public function testShouldNotBePossibleToInitWithoutEntity(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The entity must be specified.');

        $source = new QueryDesigner('', QueryDefinitionUtil::encodeDefinition(['key' => 'value']));
        $this->context->init($source);
    }
}
