<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailHelper
{
    const MAX_DESCRIPTION_LENGTH = 500;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @param ServiceLink $securityFacadeLink
     * @param string|null $cacheDir
     */
    public function __construct(ServiceLink $securityFacadeLink, $cacheDir = null)
    {
        $this->securityFacade = $securityFacadeLink->getService();
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param string $content
     * @return string
     */
    public function getStrippedBody($content)
    {
        $transformer = new SanitizeHTMLTransformer(null, $this->cacheDir);
        $content = $transformer->transform($content);
        return strip_tags($content);
    }

    /**
     * Get shorter email body
     *
     * @param string $content
     * @param int $maxLength
     * @return string
     */
    public function getShortBody($content, $maxLength = self::MAX_DESCRIPTION_LENGTH)
    {
        if (mb_strlen($content) > $maxLength) {
            $content = mb_substr($content, 0, $maxLength);
            $lastOccurrencePos = strrpos($content, ' ');
            if ($lastOccurrencePos !== false) {
                $content = mb_substr($content, 0, $lastOccurrencePos);
            }
        }

        return $content;
    }

    /**
     * @param Email $entity
     * @return bool
     */
    public function isEmailViewGranted(Email $entity)
    {
        return $this->isEmailActionGranted('VIEW', $entity);
    }

    /**
     * @param Email $entity
     * @return bool
     */
    public function isEmailEditGranted(Email $entity)
    {
        return $this->isEmailActionGranted('EDIT', $entity);
    }

    /**
     * @param string $action
     * @param Email $entity
     * @return bool
     */
    public function isEmailActionGranted($action, Email $entity)
    {
        $isGranted = false;
        foreach ($entity->getEmailUsers() as $emailUser) {
            if ($this->securityFacade->isGranted($action, $emailUser)) {
                $isGranted = true;
                break;
            }
        }

        return $isGranted;
    }
}
