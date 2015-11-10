<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Utils\FormUtils;

class TooltipFormExtension extends AbstractTypeExtension
{
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

    /** @var EntityFieldProvider */
    protected $entityFieldProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param EntityFieldProvider $entityFieldProvider
     * @param ConfigProvider $entityConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityFieldProvider $entityFieldProvider,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        $this->entityFieldProvider = $entityFieldProvider;
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

        $this->prepareModifyFieldTooltip($form, $view);
    }

    /**
     * @param FormInterface $field
     * @param FormView $view
     */
    public function prepareModifyFieldTooltip(FormInterface $field, FormView $view)
    {
        $parentOptions = $field->getParent()->getConfig()->getOptions();
        $parentClassName = isset($parentOptions['data_class']) ? $parentOptions['data_class'] : null;
        $validDescription =
            $parentClassName &&
            $this->entityConfigProvider->hasConfig($parentClassName, $field->getName()) &&
            !$field->getConfig()->getOption('description');

        if (isset($view->vars['tooltip'])) {
            if ($foundedDomain = $this->getFoundedDomain($view->vars['tooltip'])) {
                $view->vars['tooltip'] = $this->translator->trans($view->vars['tooltip'], [], $foundedDomain);
            }
        } elseif ($validDescription) {
            $tipRaw = $this->entityConfigProvider->getConfig($parentClassName, $field->getName())->get('description');
            $tip = $this->translator->trans($tipRaw);
            //if text has been added by user in gui
            if ($tipRaw !== $tip) {
                $view->vars['tooltip'] = $tip;
            }
        }
    }

    /**
     * @param string $idTranslation
     * @return bool|string
     */
    protected function getFoundedDomain($idTranslation)
    {
        if ($this->translator->hasTrans($idTranslation, 'messages')) {
            return 'messages';
        } elseif ($this->translator->hasTrans($idTranslation, 'tooltips')) {
            return 'tooltips';
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
