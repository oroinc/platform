<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Reflector;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FileReflectorTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private File $file;
    private FileReflector $reflector;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->file = new File();

        $this->reflector = new FileReflector(
            PropertyAccess::createPropertyAccessor(),
            $this->logger
        );
    }

    public function testReflectFromDigitalAssetWhenNoSourceFile(): void
    {
        $this->logger->expects($this->once())
            ->method('warning');

        $this->reflector->reflectFromDigitalAsset($this->file, $this->createMock(DigitalAsset::class));
    }

    public function testReflectFromDigitalAsset(): void
    {
        $user = new User();

        $sourceFile = new File();
        $sourceFile
            ->setFilename('sample/filename')
            ->setOriginalFilename('sample/original/filename')
            ->setMimeType('sample/type1')
            ->setFileSize(1024)
            ->setExtension('sampleext')
            ->setOwner($user);

        $digitalAsset = $this->createMock(DigitalAsset::class);
        $digitalAsset->expects($this->once())
            ->method('getSourceFile')
            ->willReturn($sourceFile);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->reflector->reflectFromDigitalAsset($this->file, $digitalAsset);

        $this->assertEquals($this->file->getFilename(), $sourceFile->getFilename());
        $this->assertEquals($this->file->getOriginalFilename(), $sourceFile->getOriginalFilename());
        $this->assertEquals($this->file->getMimeType(), $sourceFile->getMimeType());
        $this->assertEquals($this->file->getFileSize(), $sourceFile->getFileSize());
        $this->assertEquals($this->file->getExtension(), $sourceFile->getExtension());
        $this->assertEquals($this->file->getOwner(), $sourceFile->getOwner());
    }

    public function testReflectFromFile(): void
    {
        $user = new User();

        $sourceFile = new File();
        $sourceFile
            ->setFilename('sample/filename')
            ->setOriginalFilename('sample/original/filename')
            ->setMimeType('sample/type1')
            ->setFileSize(1024)
            ->setExtension('sampleext')
            ->setOwner($user);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->reflector->reflectFromFile($this->file, $sourceFile);

        $this->assertEquals($this->file->getFilename(), $sourceFile->getFilename());
        $this->assertEquals($this->file->getOriginalFilename(), $sourceFile->getOriginalFilename());
        $this->assertEquals($this->file->getMimeType(), $sourceFile->getMimeType());
        $this->assertEquals($this->file->getFileSize(), $sourceFile->getFileSize());
        $this->assertEquals($this->file->getExtension(), $sourceFile->getExtension());
        $this->assertEquals($this->file->getOwner(), $sourceFile->getOwner());
    }
}
