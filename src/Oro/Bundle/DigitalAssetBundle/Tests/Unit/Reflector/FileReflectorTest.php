<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Reflector;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FileReflectorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var FileReflector */
    private $reflector;

    /** @var File|\PHPUnit\Framework\MockObject\MockObject */
    private $file;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->reflector = new FileReflector($this->propertyAccessor);

        $this->file = new File();

        $this->setUpLoggerMock($this->reflector);
    }

    public function testReflectFromDigitalAssetWhenNoSourceFile(): void
    {
        $this->assertLoggerWarningMethodCalled();

        $this->propertyAccessor
            ->expects($this->never())
            ->method('setValue');

        $this->reflector->reflectFromDigitalAsset($this->file, $this->createMock(DigitalAsset::class));
    }

    public function testReflectFromDigitalAsset(): void
    {
        $user = new User();

        $digitalAsset = $this->createMock(DigitalAsset::class);

        $digitalAsset
            ->expects($this->once())
            ->method('getSourceFile')
            ->willReturn($sourceFile = new File());

        $sourceFile
            ->setFilename('sample/filename')
            ->setOriginalFilename('sample/original/filename')
            ->setMimeType('sample/type1')
            ->setFileSize(1024)
            ->setExtension('sampleext')
            ->setOwner($user);

        $fileReflector = new FileReflector(new PropertyAccessor());
        $fileReflector->reflectFromDigitalAsset($this->file, $digitalAsset);

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

        $fileReflector = new FileReflector(new PropertyAccessor());
        $fileReflector->reflectFromFile($this->file, $sourceFile);

        $this->assertEquals($this->file->getFilename(), $sourceFile->getFilename());
        $this->assertEquals($this->file->getOriginalFilename(), $sourceFile->getOriginalFilename());
        $this->assertEquals($this->file->getMimeType(), $sourceFile->getMimeType());
        $this->assertEquals($this->file->getFileSize(), $sourceFile->getFileSize());
        $this->assertEquals($this->file->getExtension(), $sourceFile->getExtension());
        $this->assertEquals($this->file->getOwner(), $sourceFile->getOwner());
    }
}
