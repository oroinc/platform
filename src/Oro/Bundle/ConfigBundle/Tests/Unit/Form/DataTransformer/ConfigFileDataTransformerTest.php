<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigFileDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    const FILE_ID = 1;
    const FILENAME = 'filename.jpg';

    /** @var ConfigFileDataTransformer */
    protected $transformer;

    /** @var DoctrineHelper|MockObject */
    protected $doctrineHelper;

    /** @var ValidatorInterface|MockObject */
    protected $validator;

    /**
     * @var array
     */
    protected $constraints = ['constraints'];

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->transformer = new ConfigFileDataTransformer($this->doctrineHelper, $this->validator);
        $this->transformer->setFileConstraints($this->constraints);
    }

    public function testTransformNull()
    {
        static::assertNull($this->transformer->transform(null));
    }

    public function testTransformConfigValue()
    {
        $file = $this->createMock(File::class);
        $file->expects(static::any())->method('getId')->willReturn(self::FILE_ID);
        $file->expects(static::any())->method('getFilename')->willReturn(self::FILENAME);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(static::once())->method('find')->with(self::FILE_ID)->willReturn($file);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->with(File::class)
            ->willReturn($repo);

        $transformedFile = $this->transformer->transform(self::FILE_ID);

        static::assertInstanceOf(File::class, $transformedFile);
        static::assertEquals(self::FILENAME, $transformedFile->getFilename());
    }

    public function testReverseTransformNull()
    {
        static::assertEquals('', $this->transformer->reverseTransform(null));
    }

    public function testReverseTransformEmptyFile()
    {
        $file = $this->createMock(File::class);
        $file->expects(static::any())->method('isEmptyFile')->willReturn(true);

        static::assertEquals('', $this->transformer->reverseTransform($file));
    }

    public function testReverseTransformValidFile()
    {
        $httpFile = $this->prepareHttpFile();

        $file = $this->createMock(File::class);
        $file->method('getFile')->willReturn($httpFile);
        $file->method('getId')->willReturn(self::FILE_ID);
        $file->method('isEmptyFile')->willReturn(false);
        $file->expects(static::once())->method('preUpdate');

        $this->validator->expects(static::once())
            ->method('validate')
            ->with($httpFile, $this->constraints)
            ->willReturn([]);

        $em = $this->prepareEntityManager();
        $em->expects(static::once())->method('persist');
        $em->expects(static::once())->method('flush');

        static::assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file));
    }

    public function testReverseTransformInvalidFile()
    {
        $httpFile = $this->prepareHttpFile();

        $file = $this->createMock(File::class);
        $file->method('getFile')->willReturn($httpFile);
        $file->method('getId')->willReturn(self::FILE_ID);
        $file->method('isEmptyFile')->willReturn(false);
        $file->expects(static::never())->method('preUpdate');

        $this->validator->expects(static::once())
            ->method('validate')
            ->with($httpFile, $this->constraints)
            ->willReturn(['violation']);

        $em = $this->prepareEntityManager();
        $em->expects(static::never())->method('persist');
        $em->expects(static::never())->method('flush');

        static::assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file));
    }

    public function testReverseTransformInvalidFileWithoutPersistedEntity()
    {
        $httpFile = $this->prepareHttpFile();

        $file = $this->createMock(File::class);
        $file->method('getFile')->willReturn($httpFile);
        $file->method('getId')->willReturn(null);
        $file->method('isEmptyFile')->willReturn(false);
        $file->expects(static::never())->method('preUpdate');
        $file->method('getFilename')->willReturn(self::FILENAME);

        $this->validator->expects(static::once())
            ->method('validate')
            ->with($httpFile, $this->constraints)
            ->willReturn(['violation']);

        $em = $this->prepareEntityManager();
        $em->expects(static::never())->method('persist');
        $em->expects(static::never())->method('flush');

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(static::once())->method('findOneBy')->with(['filename' => self::FILENAME])->willReturn(null);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->with(File::class)
            ->willReturn($repo);

        static::assertEquals(null, $this->transformer->reverseTransform($file));
    }

    /**
     * @return EntityManager|MockObject
     */
    private function prepareEntityManager()
    {
        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->method('getEntityManagerForClass')->with(File::class)->willReturn($em);

        return $em;
    }

    protected function prepareHttpFile(): HttpFile
    {
        return new class('', false) extends HttpFile {
            public function isFile()
            {
                return true;
            }
        };
    }
}
