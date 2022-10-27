<?php

namespace Oro\Bundle\AttachmentBundle\Formatter;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * The formatter that builds <img src="data:..."> tag for an image.
 */
class ImageEncodedFormatter implements FormatterInterface
{
    private const WIDTH_ATTRIBUTE  = 'width';
    private const HEIGHT_ATTRIBUTE = 'height';
    private const ALT_ATTRIBUTE    = 'alt';

    /** @var FileManager */
    private $fileManager;

    /** @var FileLocatorInterface */
    private $fileLocator;

    public function __construct(FileManager $fileManager, FileLocatorInterface $fileLocator)
    {
        $this->fileManager = $fileManager;
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function format($value, array $formatterArguments = [])
    {
        $altValue = $value->getOriginalFilename();
        if (array_key_exists(self::ALT_ATTRIBUTE, $formatterArguments)) {
            $altValue = $formatterArguments[self::ALT_ATTRIBUTE];
        }
        $parameters = sprintf(
            'alt = "%s"',
            $altValue
        );

        if (array_key_exists(self::HEIGHT_ATTRIBUTE, $formatterArguments)) {
            $parameters .= sprintf(
                ' height = %s',
                $formatterArguments[self::HEIGHT_ATTRIBUTE]
            );
        }

        if (array_key_exists(self::WIDTH_ATTRIBUTE, $formatterArguments)) {
            $parameters .= sprintf(
                ' width = %s',
                $formatterArguments[self::WIDTH_ATTRIBUTE]
            );
        }

        return $this->getData($value->getMimeType(), $this->fileManager->getContent($value), $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->getData(
            'image/png',
            file_get_contents($this->fileLocator->locate('@OroUIBundle/Resources/public/img/info-user.png'))
        );
    }

    /**
     * @param string $mimeType
     * @param string $content
     * @param string $parameters
     *
     * @return string
     */
    private function getData($mimeType, $content, $parameters = '')
    {
        return sprintf(
            '<img src="data:%s;base64,%s" %s/>',
            $mimeType,
            base64_encode($content),
            $parameters
        );
    }
}
