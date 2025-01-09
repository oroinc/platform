<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds support of tooltips for form fields.
 */
class TooltipFormExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    /**
     * @var array
     */
    protected $optionalParameters = [
        'tooltip',
        'tooltip_details_enabled',
        'tooltip_details_anchor',
        'tooltip_details_link',
        'tooltip_placement',
        'tooltip_parameters'
    ];

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var Translator */
    protected $translator;

    public function __construct(
        ConfigProvider $entityConfigProvider,
        Translator $translator
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
        $this->translator->setDisableResetCatalogues(true);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined($this->optionalParameters);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$form->getParent()) {
            return;
        }

        foreach ($this->optionalParameters as $parameter) {
            if (isset($options[$parameter])) {
                $view->vars[$parameter] = $options[$parameter];
            }
        }

        $this->updateTooltip($form, $view);
    }

    protected function updateTooltip(FormInterface $field, FormView $view)
    {
        $parentOptions = $field->getParent()->getConfig()->getOptions();
        $parentClassName = $parentOptions['data_class'] ?? null;
        if (!isset($view->vars['tooltip']) &&
            $parentClassName &&
            $this->entityConfigProvider->hasConfig($parentClassName, $field->getName())
        ) {
            $tooltip = $this->entityConfigProvider->getConfig($parentClassName, $field->getName())->get('description');
            if ($this->translator->hasTrans($tooltip)) {
                $view->vars['tooltip'] = $tooltip;
            }
        }
    }
}
