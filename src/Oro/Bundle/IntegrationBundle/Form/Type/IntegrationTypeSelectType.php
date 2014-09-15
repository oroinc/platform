<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\ChoiceListItem;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class IntegrationTypeSelectType extends AbstractType
{
    /** @var TypesRegistry */
    protected $registry;

    /** @var CoreAssetsHelper */
    protected $assetHelper;

    /**
     * @param TypesRegistry    $registry
     * @param CoreAssetsHelper $assetHelper
     */
    public function __construct(TypesRegistry $registry, CoreAssetsHelper $assetHelper)
    {
        $this->registry    = $registry;
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $choices = $options['choice_list']->getRemainingViews();

        if (empty($choices)) {
            $options['configs']['placeholder'] = 'oro.integration.form.no_available_integrations';
        }

        $view->vars = array_replace_recursive($view->vars, ['configs' => $options['configs']]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'empty_value' => '',
                'choices'     => $this->getChoices(),
                'configs'     => [
                    'placeholder'             => 'oro.form.choose_value',
                    'result_template_twig'    => 'OroIntegrationBundle:Autocomplete:type/result.html.twig',
                    'selection_template_twig' => 'OroIntegrationBundle:Autocomplete:type/selection.html.twig',
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_integration_type_select';
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        $choices     = [];
        $choicesData = $this->registry->getAvailableIntegrationTypesDetailedData();

        foreach ($choicesData as $typeName => $data) {
            $attributes = [];
            if (!empty($data['icon'])) {
                $attributes['data-icon'] = $this->assetHelper->getUrl($data['icon']);
            }

            $choices[$typeName] = new ChoiceListItem($data['label'], $attributes);
        }

        return $choices;
    }
}
