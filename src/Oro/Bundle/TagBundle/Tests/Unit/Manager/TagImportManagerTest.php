<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\TagBundle\Manager\TagImportManager;
use Oro\Bundle\TagBundle\Entity\Tag;

class TagImportManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TagImportManager */
    protected $tagImportManager;

    public function setUp()
    {
        $tagStorage = $this->getMockBuilder('Oro\Bundle\TagBundle\Entity\TagManager')
            ->disableOriginalConstructor()
            ->getMock();
        $tagStorage->expects($this->any())
            ->method('loadOrCreateTags')
            ->will($this->returnCallback(function (array $tagNames) {
                return array_map(
                    function ($tagName) {
                        return new Tag($tagName);
                    },
                    $tagNames
                );
            }));

        $taggableHelper = $this->getMockBuilder('Oro\Bundle\TagBundle\Helper\TaggableHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tagImportManager = new TagImportManager($tagStorage, $taggableHelper);
    }

    /**
     * @dataProvider normalizeTagsProvider
     */
    public function testNormalizeTags($tags, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->tagImportManager->normalizeTags($tags));
    }

    public function normalizeTagsProvider()
    {
        return [
            'null' => [
                null,
                ['name' => ''],
            ],
            'empty collection' => [
                new ArrayCollection(),
                ['name' => ''],
            ],
            'collection' => [
                new ArrayCollection([
                    new Tag('1'),
                    new Tag('2'),
                ]),
                [
                    'name' => '1, 2',
                ],
            ],
        ];
    }

    /**
     * @dataProvider denormalizeTagsProvider
     */
    public function testDenormalizeTags(array $data, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->tagImportManager->denormalizeTags($data));
    }

    public function denormalizeTagsProvider()
    {
        return [
            'data without "tags" field' => [
                [
                    'username' => 'admin',
                ],
                [],
            ],
            'data with "tags" field' => [
                [
                    'username' => 'admin',
                    'tags' => [
                        'name' => '   1 ,   2   ,3  ',
                    ],
                ],
                [
                    new Tag('1'),
                    new Tag('2'),
                    new Tag('3'),
                ]
            ],
        ];
    }
}
