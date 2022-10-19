<?php

namespace Oro\Bundle\AttachmentBundle\Formatter;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The formatter that builds <a href="..."> tag for an image.
 */
class ImageLinkFormatter implements FormatterInterface
{
    private const WIDTH_ATTRIBUTE  = 'width';
    private const HEIGHT_ATTRIBUTE = 'height';
    private const TITLE_ATTRIBUTE  = 'title';
    private const FORMAT_ATTRIBUTE = 'format';

    /** @var AttachmentManager */
    private $manager;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(AttachmentManager $manager, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->translator = $translator;
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

        $title = $value->getOriginalFilename();
        if (array_key_exists(self::TITLE_ATTRIBUTE, $formatterArguments)) {
            $title = $formatterArguments[self::TITLE_ATTRIBUTE];
        }

        $format = AttachmentManager::DEFAULT_FORMAT;
        if (array_key_exists(self::FORMAT_ATTRIBUTE, $formatterArguments)) {
            $format = (string)$formatterArguments[self::FORMAT_ATTRIBUTE];
        }

        return sprintf(
            '<a href="%s">%s</a>',
            $this->manager->getResizedImageUrl($value, $width, $height, $format, UrlGeneratorInterface::ABSOLUTE_URL),
            $title
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->translator->trans('oro.attachment.formatter.image_link.default');
    }
}
