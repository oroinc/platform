<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Metadata\Label;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Represents a translatable workflow name.
 */
class WorkflowLabel extends Label
{
    /**
     * {@inheritdoc}
     */
    public function trans(TranslatorInterface $translator)
    {
        return $translator->trans($this->label, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }

    /**
     * @param array $data
     *
     * @return WorkflowLabel
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new WorkflowLabel($data['label']);
    }
    // @codingStandardsIgnoreEnd
}
