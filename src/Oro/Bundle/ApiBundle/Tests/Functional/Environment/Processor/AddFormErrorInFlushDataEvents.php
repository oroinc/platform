<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * This processor emulates adding form errors in processors
 * for "pre_flush_data", "post_flush_data" and "post_save_data" events.
 */
class AddFormErrorInFlushDataEvents implements ProcessorInterface
{
    public const FORM_ERROR_PREFIX = 'FLUSH_DATA FORM ERROR - ';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var TestDepartment $department */
        $department = $context->getData();
        $departmentName = $department->getName();
        if ($departmentName
            && str_starts_with($departmentName, self::FORM_ERROR_PREFIX)
            && substr($departmentName, \strlen(self::FORM_ERROR_PREFIX)) === $context->getEvent()
        ) {
            FormUtil::addNamedFormError(
                $context->findFormField('name'),
                $context->getEvent() . ' Constraint',
                'Invalid Value'
            );
        }
    }
}
