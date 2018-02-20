<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ActionMetadataFactory
{
    const LABEL_KEY = 'label';

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Creates metadata for the given action.
     *
     * @param ActionInterface $action
     *
     * @return array
     */
    public function createActionMetadata(ActionInterface $action)
    {
        $metadata = $action->getOptions()->toArray();
        unset($metadata[ActionInterface::ACL_KEY]);

        $label = null;
        if (!empty($metadata[self::LABEL_KEY])) {
            $label = $this->translator->trans($metadata[self::LABEL_KEY]);
        }
        $metadata[self::LABEL_KEY] = $label;

        return $metadata;
    }
}
