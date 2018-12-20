<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Form\DataTransformer\ConfigFileDataTransformer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigFileDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    const FILE_ID = 1;
    const FILENAME = 'filename.jpg';

    /**
     * @var ConfigFileDataTransformer
     */
    protected $transformer;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var array
     */
    protected $constraints = ['constraints'];

    public function setUp()
    {
        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);

        $this->transformer = new ConfigFileDataTransformer(
            $this->doctrineHelper->reveal(),
            $this->validator->reveal()
        );
        $this->transformer->setFileConstraints($this->constraints);
    }

    public function testTransformNull()
    {
        $this->assertNull($this->transformer->transform(null));
    }

    public function testTransformConfigValue()
    {
        $file = $this->prophesize(File::class);
        $file->getId()->willReturn(self::FILE_ID);
        $file->getFilename()->willReturn(self::FILENAME);

        $repo = $this->prophesize(EntityRepository::class);
        $repo->find(self::FILE_ID)->willReturn($file->reveal());

        $this->doctrineHelper->getEntityRepositoryForClass(File::class)->willReturn($repo->reveal());

        $transformedFile = $this->transformer->transform(self::FILE_ID);
        $this->assertInstanceOf(File::class, $transformedFile);
        $this->assertEquals(self::FILENAME, $transformedFile->getFilename());
    }

    public function testReverseTransformNull()
    {
        $this->assertEquals('', $this->transformer->reverseTransform(null));
    }

    public function testReverseTransformEmptyFile()
    {
        $file = $this->prophesize(File::class);
        $file->isEmptyFile()->willReturn(true);

        $this->assertEquals('', $this->transformer->reverseTransform($file->reveal()));
    }

    public function testReverseTransformValidFile()
    {
        $httpFile = $this->prepareHttpFile();

        $file = $this->prepareFile($httpFile);
        $file->preUpdate()->shouldBeCalled();

        $this->validator->validate($httpFile, $this->constraints)->willReturn([]);

        $em = $this->prepareEntityManager();
        $em->persist($file)->shouldBeCalled();
        $em->flush($file)->shouldBeCalled();

        $this->assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file->reveal()));
    }

    public function testReverseTransformInvalidFile()
    {
        $httpFile = $this->prepareHttpFile();

        $file = $this->prepareFile($httpFile);
        $file->preUpdate()->shouldNotBeCalled();

        $this->validator->validate($httpFile, $this->constraints)->willReturn(['violation']);

        $em = $this->prepareEntityManager();
        $em->persist($file)->shouldNotBeCalled();
        $em->flush($file)->shouldNotBeCalled();

        $this->assertEquals(self::FILE_ID, $this->transformer->reverseTransform($file->reveal()));
    }

    /**
     * @return EntityManager
     */
    private function prepareEntityManager()
    {
        $em = $this->prophesize(EntityManager::class);

        $this->doctrineHelper->getEntityManagerForClass(File::class)->willReturn($em->reveal());

        return $em;
    }

    /**
     * @param ObjectProphecy $httpFile
     * @return File|ObjectProphecy
     */
    protected function prepareFile(ObjectProphecy $httpFile)
    {
        $file = $this->prophesize(File::class);
        $file->getFile()->willReturn($httpFile->reveal());
        $file->getId()->willReturn(self::FILE_ID);
        $file->isEmptyFile()->willReturn(false);

        return $file;
    }

    /**
     * @return HttpFile|ObjectProphecy
     */
    protected function prepareHttpFile()
    {
        $httpFile = $this->prophesize(HttpFile::class);
        $httpFile->isFile()->willReturn(true);

        return $httpFile;
    }
}
