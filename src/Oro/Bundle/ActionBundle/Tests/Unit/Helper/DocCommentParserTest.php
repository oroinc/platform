<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\DocCommentParser;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2;

class DocCommentParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var DocCommentParser */
    protected $parser;

    protected function setUp()
    {
        $this->parser = new DocCommentParser();
    }

    /**
     * @dataProvider getFullCommentDataProvider
     *
     * @param string $className
     * @param string $expected
     */
    public function testGetFullComment($className, $expected)
    {
        $this->assertSame($expected, $this->parser->getFullComment($className));
    }

    /**
     * @return array
     */
    public function getFullCommentDataProvider()
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
     *
     * @param string $className
     * @param string $expected
     */
    public function testGetShortComment($className, $expected)
    {
        $this->assertSame($expected, $this->parser->getShortComment($className));
    }

    /**
     * @return array
     */
    public function getShortCommentDataProvider()
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
