<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\GuessMimeType;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\MimeTypeGuesserInterface;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Symfony\Component\Mime\MimeTypesInterface;

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
     * @var MimeTypesInterface
     */
    private $mimeTypes;

    public function __construct(
        MimeTypeGuesserInterface $mimeTypeGuesser,
        MimeTypesInterface $mimeTypes
    ) {
        $this->mimeTypeGuesser = $mimeTypeGuesser;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function createImagineBinary(string $content): BinaryInterface
    {
        $mimeType = $this->mimeTypeGuesser->guess($content);
        $extensions = $this->mimeTypes->getExtensions($mimeType);

        return new Binary($content, $mimeType, \array_shift($extensions));
    }
}
