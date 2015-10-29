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
        foreach ($this->optionalParameters as $parameter) {
            if (isset($options[$parameter])) {
                $view->vars[$parameter] = $options[$parameter];
            }
        }

        $this->prepareModifyFieldTooltip($form, $options);
    }

    /**
     * @param FormInterface $form
     * @param array $options
     */
    public function prepareModifyFieldTooltip($form, $options)
    {
        if (empty($options['data_class'])) {
            return;
        }

        $className = $options['data_class'];
        $fields = $this->entityFieldProvider->getFields($className);

        foreach ($fields as $value) {
            $fieldName = $value['name'];
            $skipField = !$form->has($fieldName) || !$this->entityConfigProvider->hasConfig($className, $fieldName);
            //skip field if it doesn't contain in form, config or contains own custom tooltip in form
            if ($skipField || $form->get($fieldName)->getConfig()->getOption('tooltip') !== null) {
                continue;
            }
            $this->modifyFieldTooltip($className, $form, $form->get($fieldName));
        }
    }

    /**
     * @param string $className
     * @param FormInterface $form
     * @param FormInterface $field
     */
    public function modifyFieldTooltip($className, $form, $field)
    {
        if (!$field->getConfig()->getOption('description')) {
            $tooltipRaw = $this->entityConfigProvider->getConfig($className, $field->getName())->get('description');
            $tooltip = $this->translator->trans($tooltipRaw);
            //if text has been added by user in gui
            if ($tooltipRaw !== $tooltip) {
                FormUtils::replaceField($form, $field->getName(), ['tooltip' => $tooltip]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
