<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Validates that values for "parent" and "parentFieldName" fields are not blank.
 */
class ValidateFileParent implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        /** @var File $file */
        $file = $context->getData();
        if (!$file->getParentEntityClass()) {
            $fieldForm = FormUtil::findFormFieldByPropertyPath($form, 'parent');
            if (null !== $fieldForm) {
                FormUtil::addFormConstraintViolation($fieldForm, new NotBlank());
            }
        }
        if (!$file->getParentEntityFieldName()) {
            $fieldForm = FormUtil::findFormFieldByPropertyPath($form, 'parentEntityFieldName');
            if (null !== $fieldForm) {
                FormUtil::addFormConstraintViolation($fieldForm, new NotBlank());
            }
        }
    }
}
