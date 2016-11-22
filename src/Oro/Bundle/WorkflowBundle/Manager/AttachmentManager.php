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
     * @var WorkflowHelper
     */
    protected $helper;

    /**
     * @param WorkflowHelper $workflowHelper
     */
    public function setWorkflowHelper(WorkflowHelper $workflowHelper)
    {
        $this->helper = $workflowHelper;
    }

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
        /** CRM-6430 disable for workflow */
        if ($parentEntity instanceof WorkflowData) {
            return;
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
}
