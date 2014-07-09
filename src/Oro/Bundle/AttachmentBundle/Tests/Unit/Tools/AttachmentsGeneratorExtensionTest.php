<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\AttachmentsGeneratorExtension;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class AttachmentsGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttachmentsGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new AttachmentsGeneratorExtension();
    }

    /**
     * @dataProvider data
     */
    public function testIsSupports($schema, $assertSuport)
    {
        $this->assertEquals($assertSuport, $this->extension->supports($schema));
    }

    public function data()
    {
        $fieldConfig = new FieldConfigId(
            'attachment',
            'testClass',
            'test_entity_88620bc9',
            'manyToOne'
        );
        return [
            'supports' => [
                [
                    'class'=> 'Oro\Bundle\AttachmentBundle\Entity\Attachment',
                    'relation' => [[]],
                    'relationData' => [['field_id' => $fieldConfig, 'target_entity'=> 'testEntity']]
                ],
                true
            ],
            'notSupports' => [
                [
                    'class'=> 'Oro\Bundle\AttachmentBundle\Entity\File',
                ],
                false
            ]
        ];
    }
}
