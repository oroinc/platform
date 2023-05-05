<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The factory to create metadata for a datagrid action.
 */
class ActionMetadataFactory
{
    private const LABEL_KEY = 'label';
    private const TRANSLATABLE_KEY = 'translatable';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Creates metadata for the given action.
     */
    public function createActionMetadata(ActionInterface $action): array
    {
        $metadata = $action->getOptions()->toArray();
        unset($metadata[ActionInterface::ACL_KEY]);

        $label = null;
        if (!empty($metadata[self::LABEL_KEY])) {
            if (isset($metadata[self::TRANSLATABLE_KEY])) {
                $label = $metadata[self::TRANSLATABLE_KEY]
                    ? $this->translator->trans($metadata[self::LABEL_KEY])
                    : $metadata[self::LABEL_KEY];
                unset($metadata[self::TRANSLATABLE_KEY]);
            } else {
                $label = $this->translator->trans($metadata[self::LABEL_KEY]);
            }
        }
        $metadata[self::LABEL_KEY] = $label;

        return $metadata;
    }
}
