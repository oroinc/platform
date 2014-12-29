<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\AttachmentEntityGeneratorExtension;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class AttachmentEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttachmentEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new AttachmentEntityGeneratorExtension();
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
