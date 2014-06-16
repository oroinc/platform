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
            'oro_attachment_icon' => new \Twig_Function_Method($this, 'getAttachmentIcon'),
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
     * Get attachment file url
     *
     * @param Attachment $attachment
     * @param bool       $absolute
     * @param string     $type
     * @return string
     */
    public function getAttachmentUrl(Attachment $attachment, $type = 'get', $absolute = false)
    {
        return $this->manager->getAttachmentUrl($attachment, $type, $absolute);
    }

    /**
     * Get resized attachment image url
     *
     * @param Attachment $attachment
     * @param int        $width
     * @param int        $height
     * @return string
     */
    public function getResizedImageUrl(Attachment $attachment, $width = 32, $height = 32)
    {
        return $this->manager->getResizedImageUrl($attachment, $width, $height);
    }

    /**
     * Get attachment icon class
     *
     * @param Attachment $attachment
     * @return string
     */
    public function getAttachmentIcon(Attachment $attachment)
    {
        return $this->manager->getAttachmentIconClass($attachment);
    }
}
