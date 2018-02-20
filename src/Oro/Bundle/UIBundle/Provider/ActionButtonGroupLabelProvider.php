<?php

namespace Oro\Bundle\UIBundle\Provider;

use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;
use Symfony\Component\Translation\TranslatorInterface;

class ActionButtonGroupLabelProvider implements LabelProviderInterface
{
    const DEFAULT_GROUP           = 'actions';
    const DEFAULT_LABEL           = 'oro.ui.actions';
    const DEFAULT_GROUP_LABEL     = 'oro.ui.actions.%s';
    const ENTITY_NAME_PLACEHOLDER = '%entityName%';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * Parameters:
     *      groupName
     *      entityClass
     */
    public function getLabel(array $parameters)
    {
        $label  = self::DEFAULT_GROUP === $parameters['groupName']
            ? self::DEFAULT_LABEL
            : sprintf(self::DEFAULT_GROUP_LABEL, $parameters['groupName']);
        $result = $this->translator->trans($label);

        if (!empty($parameters['entityClass']) && false !== strpos($result, self::ENTITY_NAME_PLACEHOLDER)) {
            $entityNameLabel = EntityLabelBuilder::getEntityLabelTranslationKey($parameters['entityClass']);
            $result          = str_replace(
                self::ENTITY_NAME_PLACEHOLDER,
                $this->translator->trans($entityNameLabel),
                $result
            );
        }

        return $result;
    }
}
