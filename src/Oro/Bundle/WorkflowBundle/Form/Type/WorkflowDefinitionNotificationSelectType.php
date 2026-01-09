<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting workflow definitions in notification configuration.
 *
 * This form type provides a filtered list of workflows applicable to a specific entity class,
 * allowing users to select which workflows should trigger notifications.
 */
class WorkflowDefinitionNotificationSelectType extends AbstractType
{
    public const NAME = 'oro_workflow_definition_notification_select';

    /** @var WorkflowRegistry $workflowRegistry */
    protected $workflowRegistry;

    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => WorkflowDefinition::class,
            'translation_domain' => WorkflowTranslationHelper::TRANSLATION_DOMAIN,
        ]);
        $resolver->setDefined('entityClass');
        $resolver->setAllowedTypes('entityClass', ['string']);
        $resolver->setRequired('entityClass');

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $choices) {
                if (!empty($choices)) {
                    return $choices;
                }

                $choices = [];
                $workflows = $this->workflowRegistry->getWorkflowsByEntityClass($options['entityClass']);

                foreach ($workflows as $workflow) {
                    $choices[$workflow->getName()] = $workflow->getDefinition();
                }

                return $choices;
            }
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2EntityType::class;
    }
}
