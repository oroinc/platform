<?php

namespace Oro\Bundle\ConfigBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ConfigFileDataTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    protected $fileConstraints;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ValidatorInterface $validator
     * @param FileManager $fileManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ValidatorInterface $validator, FileManager $fileManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->validator = $validator;
        $this->fileManager = $fileManager;
    }

    /**
     * @param array $fileConstraints
     */
    public function setFileConstraints(array $fileConstraints)
    {
        $this->fileConstraints = $fileConstraints;
    }

    /**
     * @param null|int $value
     * @return null|object
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        return $this->doctrineHelper->getEntityRepositoryForClass(File::class)->find($value);
    }

    /**
     * @param File|null $file
     * @return int|null
     */
    public function reverseTransform($file)
    {
        if (null === $file) {
            return null;
        }

        $em = $this->doctrineHelper->getEntityManagerForClass(File::class);

        if ($file->isEmptyFile()) {
            $this->fileManager->deleteFile($file->getFilename());
            $em->remove($file);
            $em->flush($file);

            return null;
        }

        $httpFile = $file->getFile();

        if ($httpFile && $httpFile->isFile() && $this->isValidHttpFile($httpFile)) {
            $file->preUpdate();
            $em->persist($file);
            $em->flush($file);
        }

        return $file->getId();
    }

    /**
     * @param HttpFile $httpFile
     * @return bool
     */
    protected function isValidHttpFile($httpFile)
    {
        return !count($this->validator->validate($httpFile, $this->fileConstraints));
    }
}
