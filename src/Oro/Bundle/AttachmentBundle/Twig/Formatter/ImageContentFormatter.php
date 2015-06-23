<?php

namespace Oro\Bundle\AttachmentBundle\Twig\Formatter;

use Symfony\Component\HttpKernel\Config\FileLocator;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\UIBundle\Formatter\FormatterInterface;

class ImageContentFormatter implements FormatterInterface
{
    /** @var AttachmentManager */
    protected $manager;

    /** @var FileLocator */
    protected $fileLocator;

    /**
     * @param AttachmentManager $manager
     * @param FileLocator       $fileLocator
     */
    public function __construct(AttachmentManager $manager, FileLocator $fileLocator)
    {
        $this->manager     = $manager;
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterName()
    {
        return 'inline_content';
    }

    /**
     * {@inheritdoc}
     */
    public function format($parameter, array $formatterArguments = [])
    {
        return $this->getData($parameter->getMimeType(), $this->manager->getContent($parameter));
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
     *
     * @return string
     */
    protected function getData($mimeType, $content)
    {
        return sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode($content)
        );
    }
}
