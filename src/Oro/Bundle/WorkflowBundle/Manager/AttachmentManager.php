<?php

namespace Oro\Bundle\WorkflowBundle\Manager;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager as BaseManager;
use Oro\Bundle\WorkflowBundle\Utils\WorkflowHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;


class AttachmentManager extends BaseManager
{
    /**
     * Get attachment url
     *
     * @param object $parentEntity
     * @param string $fieldName
     * @param File   $entity
     * @param string $type
     * @param bool   $absolute
     * @return string
     */
    public function getFileUrl($parentEntity, $fieldName, File $entity, $type = 'get', $absolute = false)
    {

        if ($parentEntity instanceof WorkflowData) {

        }

        return $this->getAttachment(
            ClassUtils::getRealClass($parentEntity),
            $parentEntity->getId(),
            $fieldName,
            $entity,
            $type,
            $absolute
        );
    }

    /**
     * Get attachment url
     *
     * @param string $parentClass
     * @param int    $parentId
     * @param string $fieldName
     * @param File   $entity
     * @param string $type
     * @param bool   $absolute
     * @return string
     */
    public function getAttachment(
        $parentClass,
        $parentId,
        $fieldName,
        File $entity,
        $type = 'get',
        $absolute = false
    ) {
        $urlString = str_replace(
            '/',
            '_',
            base64_encode(
                implode(
                    '|',
                    [
                        $parentClass,
                        $fieldName,
                        $parentId,
                        $type,
                        $entity->getOriginalFilename()
                    ]
                )
            )
        );
        return $this->router->generate(
            'oro_attachment_file',
            [
                'codedString' => $urlString,
                'extension'   => $entity->getExtension()
            ],
            $absolute
        );
    }
}
