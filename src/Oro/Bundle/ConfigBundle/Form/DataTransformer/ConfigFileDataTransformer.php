<?php

namespace Oro\Bundle\ConfigBundle\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Transforms a value between File entity id and File entity
 */
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

    public function __construct(DoctrineHelper $doctrineHelper, ValidatorInterface $validator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->validator = $validator;
    }

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
        if (!$value) {
            return null;
        }

        $file = $this->doctrineHelper->getEntityRepositoryForClass(File::class)->find($value);
        if ($file) {
            $this->doctrineHelper->getManager()->detach($file);
        }

        return $file;
    }

    /**
     * @param File|null $file
     * @return int|null
     */
    public function reverseTransform($file)
    {
        if (null === $file) {
            return '';
        }

        if ($file->isEmptyFile()) {
            return '';
        }

        $em = $this->doctrineHelper->getEntityManagerForClass(File::class);

        $httpFile = $file->getFile();
        if ($httpFile && $httpFile->isFile() && $this->isValidHttpFile($httpFile)) {
            $file->preUpdate();
            $em->persist($file);
            $em->flush($file);
        }

        $persistedFile = $this->restoreFile($file);
        return $persistedFile ? $persistedFile->getId() : null;
    }

    /**
     * @param HttpFile $httpFile
     * @return bool
     */
    protected function isValidHttpFile($httpFile)
    {
        return !count($this->validator->validate($httpFile, $this->fileConstraints));
    }

    /**
     * @param File $file
     * @return File|null
     */
    protected function restoreFile(File $file)
    {
        if (!$file->getId()) {
            /** @var File $file */
            $file = $this
                ->doctrineHelper
                ->getEntityRepositoryForClass(File::class)
                ->findOneBy(
                    ['filename' => $file->getFilename()]
                );
        }

        return $file;
    }
}
