<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Manager\TagImportManager;

class TagImportManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagImportManager */
    private $tagImportManager;

    protected function setUp(): void
    {
        $tagStorage = $this->createMock(TagManager::class);
        $tagStorage->expects($this->any())
            ->method('loadOrCreateTags')
            ->willReturnCallback(function (array $tagNames) {
                return array_map(
                    function ($tagName) {
                        return new Tag($tagName);
                    },
                    $tagNames
                );
            });

        $taggableHelper = $this->createMock(TaggableHelper::class);

        $this->tagImportManager = new TagImportManager($tagStorage, $taggableHelper);
    }

    /**
     * @dataProvider normalizeTagsProvider
     */
    public function testNormalizeTags($tags, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->tagImportManager->normalizeTags($tags));
    }

    public function normalizeTagsProvider(): array
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

    public function denormalizeTagsProvider(): array
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
