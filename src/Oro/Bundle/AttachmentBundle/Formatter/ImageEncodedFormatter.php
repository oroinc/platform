<?php

namespace Oro\Bundle\AttachmentBundle\Formatter;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Component\Config\FileLocatorInterface;

class ImageEncodedFormatter implements FormatterInterface
{
    const WIDTH_ATTRIBUTE  = 'width';
    const HEIGHT_ATTRIBUTE = 'height';
    const ALT_ATTRIBUTE    = 'alt';

    /** @var FileManager */
    protected $fileManager;

    /** @var FileLocatorInterface */
    protected $fileLocator;

    /**
     * @param FileManager          $fileManager
     * @param FileLocatorInterface $fileLocator
     */
    public function __construct(FileManager $fileManager, FileLocatorInterface $fileLocator)
    {
        $this->fileManager = $fileManager;
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterName()
    {
        return 'image_encoded';
    }

    /**
     * {@inheritdoc}
     */
    public function format($parameter, array $formatterArguments = [])
    {
        $altValue = $parameter->getOriginalFilename();
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

        return $this->getData($parameter->getMimeType(), $this->fileManager->getContent($parameter), $parameters);
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
     * {@inheritdoc}
     */
    public function getSupportedTypes()
    {
        return ['image'];
    }

    /**
     * {@inheritdoc}
     */
    public function isDefaultFormatter()
    {
        return true;
    }

    /**
     * @param string $mimeType
     * @param string $content
     * @param string $parameters
     *
     * @return string
     */
    protected function getData($mimeType, $content, $parameters = '')
    {
        return sprintf(
            '<img src="data:%s;base64,%s" %s/>',
            $mimeType,
            base64_encode($content),
            $parameters
        );
    }
}
