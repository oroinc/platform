<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigFileDataTransformerTest extends TestCase
{
    private const FILE_ID = 1;
    private const FILENAME = 'filename.jpg';

    private array $constraints = ['constraints'];

    private DoctrineHelper&MockObject $doctrineHelper;
    private ValidatorInterface&MockObject $validator;
    private ConfigFileDataTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->transformer = new ConfigFileDataTransformer($this->doctrineHelper, $this->validator);
        $this->transformer->setFileConstraints($this->constraints);
    }

    public function testTransformNull(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformConfigValue(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('getId')
            ->willReturn(self::FILE_ID);
        $file->expects(self::any())
            ->method('getFilename')
            ->willReturn(self::FILENAME);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(self::FILE_ID)
            ->willReturn($file);

        $entityManager = $this->createMock(ObjectManager::class);
        $entityManager->expects($this->once())
            ->method('detach');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(File::class)
            ->willReturn($repo);

        $this->doctrineHelper->expects(self::once())
            ->method('getManager')
            ->willReturn($entityManager);

        $transformedFile = $this->transformer->transform(self::FILE_ID);

        self::assertInstanceOf(File::class, $transformedFile);
        self::assertEquals(self::FILENAME, $transformedFile->getFilename());
    }

    public function testReverseTransformNull(): void
    {
        self::assertEquals('', $this->transformer->reverseTransform(null));
    }

    public function testReverseTransformEmptyFile(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('isEmptyFile')
            ->willReturn(true);

        self::assertEquals('', $this->transformer->reverseTransform($file));
    }

    public function testReverseTransformValidFile(): void
    {
        $httpFile = $this->getHttpFile();

        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('getFile')
            ->willReturn($httpFile);
        $file->expects(self::any())
            ->method('getId')
            ->willReturn(self::FILE_ID);
        $file->expects(self::any())
            ->method('isEmptyFile')
            ->willReturn(false);
        $file->expects(self::once())
            ->method('preUpdate');

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($httpFile, $this->constraints)
            ->willReturn(new ConstraintViolationList());

        $em = $this->getEntityManager();
        $em->expects(self::once())
            ->method('persist');
        $em->expects(self::once())
            ->method('flush');

        self::assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file));
    }

    public function testReverseTransformInvalidFile(): void
    {
        $httpFile = $this->getHttpFile();

        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('getFile')
            ->willReturn($httpFile);
        $file->expects(self::any())
            ->method('getId')
            ->willReturn(self::FILE_ID);
        $file->expects(self::any())
            ->method('isEmptyFile')
            ->willReturn(false);
        $file->expects(self::never())
            ->method('preUpdate');

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($httpFile, $this->constraints)
            ->willReturn(ConstraintViolationList::createFromMessage('violation'));

        $em = $this->getEntityManager();
        $em->expects(self::never())
            ->method('persist');
        $em->expects(self::never())
            ->method('flush');

        self::assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file));
    }

    public function testReverseTransformInvalidFileWithoutPersistedEntity(): void
    {
        $httpFile = $this->getHttpFile();

        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('getFile')
            ->willReturn($httpFile);
        $file->expects(self::any())
            ->method('getId')
            ->willReturn(null);
        $file->expects(self::any())
            ->method('isEmptyFile')
            ->willReturn(false);
        $file->expects(self::never())
            ->method('preUpdate');
        $file->expects(self::any())
            ->method('getFilename')
            ->willReturn(self::FILENAME);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($httpFile, $this->constraints)
            ->willReturn(ConstraintViolationList::createFromMessage('violation'));

        $em = $this->getEntityManager();
        $em->expects(self::never())
            ->method('persist');
        $em->expects(self::never())
            ->method('flush');

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['filename' => self::FILENAME])
            ->willReturn(null);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(File::class)
            ->willReturn($repo);

        self::assertEquals(null, $this->transformer->reverseTransform($file));
    }

    private function getEntityManager(): EntityManagerInterface&MockObject
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with(File::class)
            ->willReturn($em);

        return $em;
    }

    private function getHttpFile(): HttpFile
    {
        return new class ('', false) extends HttpFile {
            public function isFile(): bool
            {
                return true;
            }
        };
    }
}
