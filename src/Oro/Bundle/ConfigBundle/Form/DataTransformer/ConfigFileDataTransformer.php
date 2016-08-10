<?php

namespace Oro\Bundle\ConfigBundle\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\Image;
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
        if (null === $value) {
            return null;
        }

        return $this->doctrineHelper->getEntityRepositoryForClass(File::class)->find($value);
    }

    /**
     * @param File $file
     * @return int|null
     */
    public function reverseTransform($file)
    {
        $em = $this->doctrineHelper->getEntityManagerForClass(File::class);

        if (null === $file) {
            return null;
        }

        if ($file->isEmptyFile()) {
            $em->remove($file);
            $em->flush($file);

            return null;
        }
        if (
            $file->getFile() &&
            $file->getFile()->isFile() &&
            0 === count($this->validator->validate($file->getFile(), $this->fileConstraints))
        ) {
            $file->preUpdate();
            $em->persist($file);
            $em->flush($file);
        }

        return $file->getId();
    }
}
