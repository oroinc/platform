<?php

namespace Oro\Bundle\AttachmentBundle\Formatter;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The formatter that returns image URL.
 */
class ImageSrcFormatter implements FormatterInterface
{
    private const WIDTH_ATTRIBUTE  = 'width';
    private const HEIGHT_ATTRIBUTE = 'height';

    /** @var AttachmentManager */
    private $manager;

    public function __construct(AttachmentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function format($value, array $formatterArguments = [])
    {
        $height = AttachmentManager::DEFAULT_IMAGE_HEIGHT;
        if (array_key_exists(self::HEIGHT_ATTRIBUTE, $formatterArguments)) {
            $height = (int)$formatterArguments[self::HEIGHT_ATTRIBUTE];
        }

        $width = AttachmentManager::DEFAULT_IMAGE_WIDTH;
        if (array_key_exists(self::WIDTH_ATTRIBUTE, $formatterArguments)) {
            $width = (int)$formatterArguments[self::WIDTH_ATTRIBUTE];
        }

        return $this->manager->getResizedImageUrl($value, $width, $height, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return '#';
    }
}
