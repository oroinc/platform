<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\GuessMimeType;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\MimeTypeGuesserInterface;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;

/**
 * Creates Liip Imagine Binary from raw file content, guesses extension and mime type.
 */
class GuessMimeTypeByFileContentFactory implements ImagineBinaryByFileContentFactoryInterface
{
    /**
     * @var MimeTypeGuesserInterface
     */
    private $mimeTypeGuesser;

    /**
     * @var ExtensionGuesserInterface
     */
    private $extensionGuesser;

    public function __construct(
        MimeTypeGuesserInterface $mimeTypeGuesser,
        ExtensionGuesserInterface $extensionGuesser
    ) {
        $this->mimeTypeGuesser = $mimeTypeGuesser;
        $this->extensionGuesser = $extensionGuesser;
    }

    /**
     * {@inheritDoc}
     */
    public function createImagineBinary(string $content): BinaryInterface
    {
        $mimeType = $this->mimeTypeGuesser->guess($content);
        $format = $this->extensionGuesser->guess($mimeType);

        return new Binary($content, $mimeType, $format);
    }
}
