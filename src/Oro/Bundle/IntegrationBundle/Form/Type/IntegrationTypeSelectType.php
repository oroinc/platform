<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationTypeSelectType extends AbstractType
{
    /** @var TypesRegistry */
    protected $registry;

    /** @var AssetHelper */
    protected $assetHelper;

    /** @var array */
    protected $itemsCache;

    /**
     * @param TypesRegistry $registry
     * @param AssetHelper   $assetHelper
     */
    public function __construct(TypesRegistry $registry, AssetHelper $assetHelper)
    {
        $this->registry    = $registry;
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $choices = $options['choices'];

        if (empty($choices)) {
            $options['configs']['placeholder'] = 'oro.integration.form.no_available_integrations';
        }

        $view->vars = array_replace_recursive($view->vars, ['configs' => $options['configs']]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'     => $this->getChoices(),
                'choice_attr' => function ($choice) {
                    return $this->getChoiceAttributes($choice);
                },
                'configs'     => [
                    'placeholder' => 'oro.form.choose_value',
                    'showIcon'    => true,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_integration_type_select';
    }

    /**
     * Returns a list of available integration types
     *
     * @return array [{integration type} => [{attr1} => {val1}, ...], ...]
     */
    protected function getAvailableIntegrationTypes()
    {
        if (null === $this->itemsCache) {
            $this->itemsCache = $this->registry->getAvailableIntegrationTypesDetailedData();
        }

        return $this->itemsCache;
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        $choices = [];
        foreach ($this->getAvailableIntegrationTypes() as $typeName => $data) {
            $choices[$data['label']] = $typeName;
        }

        return $choices;
    }

    /**
     * Returns a list of choice attributes for the given integration type
     *
     * @param string $typeName
     *
     * @return array
     */
    protected function getChoiceAttributes($typeName)
    {
        $attributes = [];
        $data       = !empty($this->itemsCache[$typeName]) ? $this->itemsCache[$typeName] : [];
        if (!empty($data['icon'])) {
            $attributes['data-icon'] = $this->assetHelper->getUrl($data['icon']);
        }

        return $attributes;
    }
}
