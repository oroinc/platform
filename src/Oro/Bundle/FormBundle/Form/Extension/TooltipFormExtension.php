<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;

class TooltipFormExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    const DEFAULT_TRANSLATE_DOMAIN = 'messages';
    const TOOLTIPS_TRANSLATE_DOMAIN = 'tooltips';

    /**
     * @var array
     */
    protected $optionalParameters = array(
        'tooltip',
        'tooltip_details_enabled',
        'tooltip_details_anchor',
        'tooltip_details_link',
        'tooltip_placement',
        'tooltip_parameters'
    );

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var Translator */
    protected $translator;

    /**
     * @param ConfigProvider $entityConfigProvider
     * @param Translator $translator
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        Translator $translator
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional($this->optionalParameters);
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

    /**
     * @param FormInterface $field
     * @param FormView $view
     */
    protected function updateTooltip(FormInterface $field, FormView $view)
    {
        $parentOptions = $field->getParent()->getConfig()->getOptions();
        $parentClassName = isset($parentOptions['data_class']) ? $parentOptions['data_class'] : null;
        if (!isset($view->vars['tooltip']) &&
            $parentClassName &&
            $this->entityConfigProvider->hasConfig($parentClassName, $field->getName())
        ) {
            $tooltip = $this->entityConfigProvider->getConfig($parentClassName, $field->getName())->get('description');
            //@deprecated 1.9.0:1.11.0 tooltips.*.yml will be removed. Use Resources/translations/messages.*.yml instead
            if ($this->translator->hasTrans($tooltip, self::DEFAULT_TRANSLATE_DOMAIN) ||
                $this->translator->hasTrans($tooltip, self::TOOLTIPS_TRANSLATE_DOMAIN)
            ) {
                $view->vars['tooltip'] = $tooltip;
            }
        }
    }
}
