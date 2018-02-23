<?php

namespace Oro\Bundle\AttachmentBundle\Formatter;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class ImageLinkFormatter implements FormatterInterface
{
    const WIDTH_ATTRIBUTE  = 'width';
    const HEIGHT_ATTRIBUTE = 'height';
    const TITLE_ATTRIBUTE  = 'title';

    /** @var AttachmentManager */
    protected $manager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param AttachmentManager   $manager
     * @param TranslatorInterface $translator
     */
    public function __construct(AttachmentManager $manager, TranslatorInterface $translator)
    {
        $this->manager    = $manager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterName()
    {
        return 'image_link';
    }

    /**
     * {@inheritdoc}
     */
    public function format($parameter, array $formatterArguments = [])
    {
        $height = AttachmentManager::DEFAULT_IMAGE_HEIGHT;
        if (array_key_exists(self::HEIGHT_ATTRIBUTE, $formatterArguments)) {
            $height = (int)$formatterArguments[self::HEIGHT_ATTRIBUTE];
        }

        $width = AttachmentManager::DEFAULT_IMAGE_WIDTH;
        if (array_key_exists(self::WIDTH_ATTRIBUTE, $formatterArguments)) {
            $width = (int)$formatterArguments[self::WIDTH_ATTRIBUTE];
        }

        $title = $parameter->getOriginalFilename();
        if (array_key_exists(self::TITLE_ATTRIBUTE, $formatterArguments)) {
            $title = $formatterArguments[self::TITLE_ATTRIBUTE];
        }

        return sprintf(
            '<a href="%s">%s</a>',
            $this->manager->getResizedImageUrl($parameter, $width, $height, Router::ABSOLUTE_URL),
            $title
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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return $this->translator->trans('oro.attachment.formatter.image_link.default');
    }
}
