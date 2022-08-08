<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentEntityGeneratorExtension;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class AttachmentEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentEntityGeneratorExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new AttachmentEntityGeneratorExtension();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(array $schema, bool $expected)
    {
        self::assertSame($expected, $this->extension->supports($schema));
    }

    public function supportsDataProvider(): array
    {
        return [
            'supports' => [
                [
                    'class' => Attachment::class,
                    'relation' => [[]],
                    'relationData' => [[
                        'field_id' => new FieldConfigId('attachment', 'testClass', 'test_entity_88620bc9', 'manyToOne'),
                        'target_entity' => 'testEntity',
                        'state' => 'Active'
                    ]]
                ],
                true
            ],
            'notSupports' => [
                [
                    'class'=> File::class,
                ],
                false
            ]
        ];
    }
}
