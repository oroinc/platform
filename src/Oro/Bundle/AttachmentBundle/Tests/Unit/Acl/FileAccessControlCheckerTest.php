<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Acl;

use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;

class FileAccessControlCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentEntityConfigProvider;

    /** @var FileAccessControlChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->attachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);

        $this->checker = new FileAccessControlChecker($this->attachmentEntityConfigProvider);
    }

    public function testIsCoveredByAclWhenStoredExternally(): void
    {
        $file = new File();
        $file->setExternalUrl('http://example.org/image.png');

        $this->attachmentEntityConfigProvider
            ->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->checker->isCoveredByAcl($file));
    }

    /**
     * @dataProvider isCoveredByAclWhenNotEnoughDataProvider
     */
    public function testIsCoveredByAclWhenNotEnoughData(File $file): void
    {
        $this->attachmentEntityConfigProvider
            ->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->checker->isCoveredByAcl($file));
    }

    public function isCoveredByAclWhenNotEnoughDataProvider(): array
    {
        return [
            [new File()],
            [(new File())->setParentEntityClass(\stdClass::class)],
            [(new File())->setParentEntityId(1)],
            [(new File())->setParentEntityFieldName('sample-field')],
        ];
    }

    public function testIsCoveredByAcl(): void
    {
        $file = (new File())
            ->setParentEntityClass($parentEntityClass = \stdClass::class)
            ->setParentEntityId(1)
            ->setParentEntityFieldName($parentEntityFieldName = 'sample-field');

        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with($parentEntityClass, $parentEntityFieldName)
        ->willReturn($config  = $this->createMock(Config::class));

        $config
            ->expects(self::once())
            ->method('get')
            ->with('acl_protected', false, true)
            ->willReturn($result = true);

        self::assertSame($result, $this->checker->isCoveredByAcl($file));
    }

    public function testIsCoveredByAclWhenNoEntityFieldConfig(): void
    {
        $file = (new File())
            ->setParentEntityClass($parentEntityClass = \stdClass::class)
            ->setParentEntityId(1)
            ->setParentEntityFieldName($parentEntityFieldName = 'sample-field');

        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with($parentEntityClass, $parentEntityFieldName)
            ->willReturn(null);

        self::assertFalse($this->checker->isCoveredByAcl($file));
    }
}
