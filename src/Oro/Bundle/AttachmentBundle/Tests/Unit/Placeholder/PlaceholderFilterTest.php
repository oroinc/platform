<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\AttachmentBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attachmentAssociationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var PlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->attachmentAssociationHelper = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new PlaceholderFilter($this->attachmentAssociationHelper, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->attachmentAssociationHelper, $this->doctrineHelper, $this->filter);
    }

    /**
     * @param null|object $entity
     * @param bool        $attachmentAssociationHelperReturn
     * @param bool        $isNewRecord
     * @param bool        $isManaged
     * @param bool        $expected
     * @dataProvider configResultProvider
     */
    public function testIsAttachmentAssociationEnabled(
        $entity,
        $attachmentAssociationHelperReturn,
        $isNewRecord,
        $isManaged,
        $expected
    ) {
        $this->attachmentAssociationHelper
            ->expects(is_object($entity) && !$isNewRecord ? $this->once() : $this->never())
            ->method('isAttachmentAssociationEnabled')
            ->with(is_object($entity) ? get_class($entity) : null)
            ->willReturn($attachmentAssociationHelperReturn);

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
                'entity'                 => $this->createMock('\stdClass'),
                'attachmentConfigReturn' => true,
                'isNewRecord'            => false,
                'isManaged'              => true,
                'expected'               => true
            ],
            'existing entity without association' => [
                'entity'                 => $this->createMock('\stdClass'),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => false,
                'isManaged'              => true,
                'expected'               => false
            ],
            'new entity without association' => [
                'entity'                 => $this->createMock('\stdClass'),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => true,
                'isManaged'              => true,
                'expected'               => false
            ],
            'not managed entity' => [
                'entity'                 => $this->createMock('\stdClass'),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => true,
                'isManaged'              => false,
                'expected'               => false
            ]
        ];
    }
}
