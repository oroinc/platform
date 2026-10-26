<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetRegistry;
use PHPUnit\Framework\TestCase;

final class FileValidationTargetRegistryTest extends TestCase
{
    private FileValidationTargetRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = new FileValidationTargetRegistry();
    }

    public function testGetForNotRegisteredFile(): void
    {
        self::assertNull($this->registry->get(new File()));
    }

    public function testGetForRegisteredFile(): void
    {
        $file = new File();
        $target = new FileValidationTarget(\stdClass::class, 'avatar');

        $this->registry->register($file, $target);

        self::assertSame($target, $this->registry->get($file));
    }

    public function testClear(): void
    {
        $file = new File();
        $anotherFile = new File();
        $this->registry->register($file, new FileValidationTarget(\stdClass::class, 'avatar'));
        $this->registry->register($anotherFile, new FileValidationTarget(\stdClass::class, 'logo'));

        $this->registry->clear();

        self::assertNull($this->registry->get($file));
        self::assertNull($this->registry->get($anotherFile));
    }
}
