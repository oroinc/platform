<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory;

use Liip\ImagineBundle\Binary\BinaryInterface;

/**
 * Defines the contract for creating Imagine binary objects from file content.
 *
 * This interface should be implemented by factories that convert raw binary file content
 * into Imagine {@see BinaryInterface} objects. These objects are used by the Liip Imagine bundle
 * for image processing operations such as filtering, resizing, and format conversion.
 * Implementations handle the creation of properly formatted binary objects that can be
 * processed by the Imagine image manipulation library.
 */
interface ImagineBinaryByFileContentFactoryInterface
{
    /**
     * @param string $content Binary string
     *
     * @return BinaryInterface
     */
    public function createImagineBinary(string $content): BinaryInterface;
}
