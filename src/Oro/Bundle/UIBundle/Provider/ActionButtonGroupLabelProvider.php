<?php

namespace Oro\Bundle\UIBundle\Provider;

use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides labels for action button groups.
 */
class ActionButtonGroupLabelProvider implements LabelProviderInterface
{
    public const DEFAULT_GROUP           = 'actions';
    public const DEFAULT_LABEL           = 'oro.ui.actions';
    public const DEFAULT_GROUP_LABEL     = 'oro.ui.actions.%s';
    public const ENTITY_NAME_PLACEHOLDER = '%entityName%';

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function getLabel(array $parameters)
    {
        $label = self::DEFAULT_GROUP === $parameters['groupName']
            ? self::DEFAULT_LABEL
            : sprintf(self::DEFAULT_GROUP_LABEL, $parameters['groupName']);
        $result = $this->translator->trans($label);

        if (!empty($parameters['entityClass']) && str_contains($result, self::ENTITY_NAME_PLACEHOLDER)) {
            $entityNameLabel = EntityLabelBuilder::getEntityLabelTranslationKey($parameters['entityClass']);
            $result = str_replace(
                self::ENTITY_NAME_PLACEHOLDER,
                $this->translator->trans($entityNameLabel),
                $result
            );
        }

        return $result;
    }
}
