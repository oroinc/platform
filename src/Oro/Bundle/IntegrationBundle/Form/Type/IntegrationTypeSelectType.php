<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Templating\Helper\CoreAssetsHelper;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class IntegrationTypeSelectType extends AbstractType
{
    /** @var TypesRegistry */
    protected $registry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var CoreAssetsHelper */
    protected $assetHelper;

    /**
     * @param TypesRegistry       $registry
     * @param TranslatorInterface $translator
     * @param CoreAssetsHelper    $assetHelper
     */
    public function __construct(TypesRegistry $registry, TranslatorInterface $translator, CoreAssetsHelper $assetHelper)
    {
        $this->registry = $registry;
        $this->translator = $translator;
        $this->assetHelper = $assetHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->rebuildChoiceList($options['choice_list']->getRemainingViews());

        $vars = ['configs' => $options['configs']];

        if ($form->getData()) {
            $vars['attr'] = [
                'data-selected-data' => json_encode([['id' => $form->getData(), 'text' => $form->getData()]])
            ];
        }

        $view->vars = array_replace_recursive($view->vars, $vars);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'empty_value' => '',
                'configs'     => [
                    'placeholder'             => 'oro.form.choose_value',
                    'result_template_twig'    => 'OroIntegrationBundle:Autocomplete:select/result.html.twig',
                    'selection_template_twig' => 'OroIntegrationBundle:Autocomplete:select/selection.html.twig',
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
     * @param array $choiceList
     */
    protected function rebuildChoiceList(array $choiceList)
    {
        foreach ($choiceList as $row) {
            $data = json_decode($row->label, 1);
            $data['label'] = $this->translator->trans($data['label']);
            if (!empty($data['icon'])) {
                $data['icon'] = $this->assetHelper->getUrl($data['icon']);
            }
            $row->label = json_encode($data);
        }
    }
}
