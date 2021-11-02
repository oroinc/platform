<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\AttachmentBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\AttachmentBundle\Tools\AttachmentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentAssociationHelper;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PlaceholderFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->attachmentAssociationHelper = $this->createMock(AttachmentAssociationHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->filter = new PlaceholderFilter($this->attachmentAssociationHelper, $this->doctrineHelper);
    }

    /**
     * @dataProvider configResultProvider
     */
    public function testIsAttachmentAssociationEnabled(
        ?object $entity,
        bool $attachmentAssociationHelperReturn,
        bool $isNewRecord,
        bool $isManaged,
        bool $expected
    ) {
        $this->attachmentAssociationHelper->expects(is_object($entity) && !$isNewRecord ? self::once() : self::never())
            ->method('isAttachmentAssociationEnabled')
            ->with(is_object($entity) ? get_class($entity) : null)
            ->willReturn($attachmentAssociationHelperReturn);

        $this->doctrineHelper->expects(is_object($entity) && $isManaged ? self::once() : self::never())
            ->method('isNewEntity')
            ->willReturn($isNewRecord);

        $this->doctrineHelper->expects(is_object($entity) ? self::once() : self::never())
            ->method('isManageableEntity')
            ->willReturn($isManaged);

        $actual = $this->filter->isAttachmentAssociationEnabled($entity);
        self::assertEquals($expected, $actual);
    }

    public function configResultProvider(): array
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
                'entity'                 => $this->createMock(\stdClass::class),
                'attachmentConfigReturn' => true,
                'isNewRecord'            => false,
                'isManaged'              => true,
                'expected'               => true
            ],
            'existing entity without association' => [
                'entity'                 => $this->createMock(\stdClass::class),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => false,
                'isManaged'              => true,
                'expected'               => false
            ],
            'new entity without association' => [
                'entity'                 => $this->createMock(\stdClass::class),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => true,
                'isManaged'              => true,
                'expected'               => false
            ],
            'not managed entity' => [
                'entity'                 => $this->createMock(\stdClass::class),
                'attachmentConfigReturn' => false,
                'isNewRecord'            => true,
                'isManaged'              => false,
                'expected'               => false
            ]
        ];
    }
}
