<?php

namespace Oro\Bundle\AttachmentBundle\Twig\Formatter;

use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

class ImageUrlFormatter implements FormatterInterface
{
    const WIDTH_ATTRIBUTE  = 'width';
    const HEIGHT_ATTRIBUTE = 'height';

    /** @var AttachmentManager */
    protected $manager;

    /**
     * @param AttachmentManager $manager
     */
    public function __construct(AttachmentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterName()
    {
        return 'image_url';
    }

    /**
     * {@inheritdoc}
     */
    public function format($parameter, array $formatterArguments = [])
    {
        $height = AttachmentManager::DEFAULT_IMAGE_HEIGHT;
        if (array_key_exists(self::HEIGHT_ATTRIBUTE, $formatterArguments)) {
            $height = (int) $formatterArguments[self::HEIGHT_ATTRIBUTE];
        }

        $width = AttachmentManager::DEFAULT_IMAGE_WIDTH;
        if (array_key_exists(self::WIDTH_ATTRIBUTE, $formatterArguments)) {
            $width = (int) $formatterArguments[self::WIDTH_ATTRIBUTE];
        }

        return $this->manager->getResizedImageUrl($parameter, $width, $height);
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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return '#';
    }
}
