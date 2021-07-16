<?php

namespace Oro\Bundle\DigitalAssetBundle\Reflector;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Reflects file from source file of digital asset.
 */
class FileReflector implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->logger = new NullLogger();
    }

    /**
     * Populates file with properties from source file of the given digital asset.
     * Copies the following properties:
     * - filename
     * - originalFilename
     * - extension
     * - mimeType
     * - fileSize
     * - owner
     */
    public function reflectFromDigitalAsset(File $file, DigitalAsset $digitalAsset): bool
    {
        $sourceFile = $digitalAsset->getSourceFile();
        if (!$sourceFile) {
            $this->logger->warning(
                sprintf('DigitalAsset #%d was not expected to have an empty source file', $digitalAsset->getId()),
                ['digitalAsset' => $digitalAsset]
            );

            return false;
        }

        $this->reflectFromFile($file, $sourceFile);

        return true;
    }

    /**
     * Populates file with properties from the given source file.
     * Copies the following properties:
     * - filename
     * - originalFilename
     * - extension
     * - mimeType
     * - fileSize
     * - owner
     */
    public function reflectFromFile(File $file, File $sourceFile): void
    {
        $propertiesToUpdate = ['filename', 'extension', 'originalFilename', 'mimeType', 'fileSize', 'owner'];
        foreach ($propertiesToUpdate as $property) {
            $this->propertyAccessor->setValue(
                $file,
                $property,
                $this->propertyAccessor->getValue($sourceFile, $property)
            );
        }
    }
}
