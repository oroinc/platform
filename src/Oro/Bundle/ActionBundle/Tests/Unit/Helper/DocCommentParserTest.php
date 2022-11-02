<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\DocCommentParser;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2;

class DocCommentParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var DocCommentParser */
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new DocCommentParser();
    }

    /**
     * @dataProvider getFullCommentDataProvider
     */
    public function testGetFullComment(string $className, string $expected)
    {
        $this->assertSame($expected, $this->parser->getFullComment($className));
    }

    public function getFullCommentDataProvider(): array
    {
        return [
            'with comment' => [
                'className' => TestEntity1::class,
                'expected' => "This is description\n of the class\n \n Class TestEntity1",
            ],
            'empty comment' => [
                'className' => TestEntity2::class,
                'expected' => '',
            ],
        ];
    }

    /**
     * @dataProvider getShortCommentDataProvider
     */
    public function testGetShortComment(string $className, string $expected)
    {
        $this->assertSame($expected, $this->parser->getShortComment($className));
    }

    public function getShortCommentDataProvider(): array
    {
        return [
            'full comment' => [
                'className' => TestEntity1::class,
                'expected' => "This is description\n of the class",
            ],
            'empty comment' => [
                'className' => TestEntity2::class,
                'expected' => '',
            ],
        ];
    }
}
