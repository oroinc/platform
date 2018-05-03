<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\AbstractGuesser;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;

class VariableGuesser extends AbstractGuesser
{
    const DEFAULT_CONSTRAINT_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param FormRegistry $formRegistry
     * @param ManagerRegistry $managerRegistry
     * @param ConfigProvider $entityConfigProvider
     * @param ConfigProvider $formConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        FormRegistry $formRegistry,
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $formConfigProvider,
        TranslatorInterface $translator
    ) {
        parent::__construct($formRegistry, $managerRegistry, $entityConfigProvider, $formConfigProvider);

        $this->translator = $translator;
    }

    /**
     * @param Variable $variable
     *
     * @return null|TypeGuess
     */
    public function guessVariableForm(Variable $variable)
    {
        $type = $variable->getType();
        if ('entity' === $type) {
            list($formType, $formOptions) = $this->getEntityForm($variable);

            return new TypeGuess($formType, $formOptions, TypeGuess::VERY_HIGH_CONFIDENCE);
        }

        if (!isset($this->formTypeMapping[$type])) {
            return null;
        }

        $formType = $this->formTypeMapping[$type]['type'];
        $formOptions = array_merge_recursive($this->formTypeMapping[$type]['options'], $variable->getFormOptions());

        $formOptions = $this->setVariableFormOptions($variable, $formOptions);

        return new TypeGuess($formType, $formOptions, TypeGuess::VERY_HIGH_CONFIDENCE);
    }

    /**
     * @param Variable $variable
     * @param array    $formOptions
     *
     * @return array
     */
    protected function setVariableFormOptions(Variable $variable, array $formOptions)
    {
        if (null !== $variable->getLabel()) {
            $formOptions['label'] = $this->translator
                ->trans($variable->getLabel(), [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);
        }

        if (null !== $variable->getValue()) {
            $formOptions['data'] = $variable->getValue();
        }

        if (isset($formOptions['tooltip'])) {
            $formOptions['tooltip'] = $this->translator
                ->trans($formOptions['tooltip'], [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);
        }

        return $this->processFormConstraints($formOptions);
    }

    /**
     * @param array $formOptions
     * @return array
     */
    protected function processFormConstraints(array $formOptions)
    {
        if (!isset($formOptions['constraints']) || !is_array($formOptions['constraints'])) {
            return $formOptions;
        }

        foreach ($formOptions['constraints'] as $constraint => $constraintOptions) {
            if ($constraintOptions instanceof Constraint) {
                continue;
            }

            unset($formOptions['constraints'][$constraint]);
            if (false === strpos($constraint, '\\')) {
                $constraint = sprintf('%s%s', self::DEFAULT_CONSTRAINT_NAMESPACE, $constraint);
            }
            $formOptions['constraints'][] = new $constraint($constraintOptions);
        }

        return $formOptions;
    }

    /**
     * @param Variable $variable
     *
     * @return array
     */
    protected function getEntityForm(Variable $variable)
    {
        $entityClass = $variable->getOption('class');
        $formType = $variable->getOption('form_type');

        $formOptions = $variable->getFormOptions();
        if (!$formType) {
            if ($this->formConfigProvider->hasConfig($entityClass)) {
                $formConfig = $this->formConfigProvider->getConfig($entityClass);
                $formType = $formConfig->get('form_type');

                if (!$formType && !isset($formOptions['class'])) {
                    $formOptions['class'] = $entityClass;
                }

                $identifier = $variable->getOption('identifier');
                if (!$formType && $identifier) {
                    $formOptions['choice_value'] = $identifier;
                }

                $formOptions = array_merge($formConfig->get('form_options', false, []), $formOptions);
            } else {
                $formType = EntityType::class;
                $formOptions = array_merge($formOptions, [
                    'class' => $entityClass,
                    'multiple' => false
                ]);
            }
        }

        $formOptions = $this->setVariableFormOptions($variable, $formOptions);

        return [$formType, $formOptions];
    }
}
