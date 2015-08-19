<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig;
use Oro\Bundle\AttachmentBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|AttachmentConfig */
    protected $attachmentConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var PlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->attachmentConfig = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new PlaceholderFilter($this->attachmentConfig, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->attachmentConfig, $this->doctrineHelper, $this->filter);
    }

    /**
     * @param null|object $entity
     * @param bool        $attachmentConfigReturn
     * @param bool        $isNewRecord
     * @param bool        $isManaged
     * @param bool        $expected
     * @dataProvider configResultProvider
     */
    public function testIsAttachmentAssociationEnabled($entity, $attachmentConfigReturn, $isNewRecord, $isManaged, $expected)
    {
        $this->attachmentConfig->expects(is_object($entity) && !$isNewRecord ? $this->once() : $this->never())
            ->method('isAttachmentAssociationEnabled')
            ->with($entity)
            ->willReturn($attachmentConfigReturn);

        $this->doctrineHelper->expects(is_object($entity) && $isManaged ? $this->once() : $this->never())
            ->method('isNewEntity')
            ->willReturn($isNewRecord);

        $this->doctrineHelper->expects(is_object($entity) ? $this->once() : $this->never())
            ->method('isManageableEntity')
            ->willReturn($isManaged);

        $actual = $this->filter->isAttachmentAssociationEnabled($entity);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function configResultProvider()
    {
        return [
            'null entity' => [
                'entity'                 => null,
                'attachmentConfigReturn' => true,
                'isNewRecord'            => true,
                'isManaged'              => true,
                'expected'               => false
            ],
            'existing entity with association' => [
                'entity'                 => $this->getMock('\stdClass'),
                'attachmentConfigReturn' => true,
                'isNewRecord'            => false,
                'isManaged'              => true,
                'expected'               => true
            ],
            'existing entity without association' => [
                'entity'                 => $this->getMock('\stdClass'),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => false,
                'isManaged'              => true,
                'expected'               => false
            ],
            'new entity without association' => [
                'entity'                 => $this->getMock('\stdClass'),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => true,
                'isManaged'              => true,
                'expected'               => false
            ],
            'not managed entity' => [
                'entity'                 => $this->getMock('\stdClass'),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => true,
                'isManaged'              => false,
                'expected'               => false
            ]
        ];
    }
}
