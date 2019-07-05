<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Acl;

use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class FileAccessControlCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FileAccessControlChecker */
    private $checker;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->checker = new FileAccessControlChecker($this->configManager);
    }

    /**
     * @dataProvider isCoveredByAclWhenNotEnoughDataProvider
     *
     * @param File $file
     */
    public function testIsCoveredByAclWhenNotEnoughData(File $file): void
    {
        $this->configManager
            ->expects(self::never())
            ->method('getFieldConfig');

        self::assertFalse($this->checker->isCoveredByAcl($file));
    }

    /**
     * @return array
     */
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

        $this->configManager
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', $parentEntityClass, $parentEntityFieldName)
        ->willReturn($config  = $this->createMock(Config::class));

        $config
            ->expects(self::once())
            ->method('get')
            ->with('acl_protected', false, true)
            ->willReturn($result = true);

        self::assertSame($result, $this->checker->isCoveredByAcl($file));
    }
}
