<?php

namespace Oro\Bundle\ConfigBundle\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File as HttpFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @param DoctrineHelper $doctrineHelper
     * @param ValidatorInterface $validator
     */
    public function __construct(DoctrineHelper $doctrineHelper, ValidatorInterface $validator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->validator = $validator;
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
        if (!$value) {
            return null;
        }

        $file = $this->doctrineHelper->getEntityRepositoryForClass(File::class)->find($value);

        return $file ? clone $file : null;
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

        return $this->restoreFile($file)->getId();
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
     * @return File
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
