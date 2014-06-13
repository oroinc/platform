<?php

namespace Oro\Bundle\AttachmentBundle\Twig;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

class AttachmentExtension extends \Twig_Extension
{
    /**
     * @var AttachmentManager
     */
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
    public function getFunctions()
    {
        return [
            'oro_attachment_url' => new \Twig_Function_Method($this, 'getAttachmentUrl'),
            'oro_resized_attachment_url' => new \Twig_Function_Method($this, 'getResizedImageUrl'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_attachment';
    }

    /**
     * @param Attachment $attachment
     * @param bool $absolute
     * @param string $type
     * @return string
     */
    public function getAttachmentUrl(Attachment $attachment, $absolute = false, $type = 'get')
    {
        return $this->manager->getAttachmentUrl($attachment, $absolute, $type);
    }

    /**
     * @param Attachment $attachment
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getResizedImageUrl(Attachment $attachment, $width = 100, $height = 100)
    {
        return $this->manager->getResizedImageUrl($attachment, $width, $height);
    }
}
