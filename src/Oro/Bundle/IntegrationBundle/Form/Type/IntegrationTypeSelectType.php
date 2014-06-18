<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Composer\DependencyResolver\Transaction;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\DependencyInjection\Container;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class IntegrationTypeSelectType extends AbstractType
{
    /** @var TypesRegistry */
    protected $registry;

    protected $container;

    public function __construct(TypesRegistry $registry, Container $container)
    {
        $this->registry = $registry;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $options['choice_list'] = $this->rebuildChoiceList($options['choice_list']->getRemainingViews());

        parent::buildView($view, $form, $options);

        $vars = [
            'configs' => $options['configs'],
            'choices' => $options['choice_list']->getRemainingViews()
        ];

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
     * @param string $subject
     *
     * @return string
     */
    protected function getTranslation($subject)
    {
        return $this->container->get('translator')->trans($subject);
    }

    /**
     * @param array $choiceList
     *
     * @return SimpleChoiceList
     */
    protected function rebuildChoiceList(array $choiceList)
    {
        $newChoiceList = [];

        foreach ($choiceList as $row) {
            $label = json_decode($row->label, 1);
            $label['label'] = $this->getTranslation($label['label']);
            $newChoiceList[$row->value] = json_encode($label);
        }
        return new SimpleChoiceList($newChoiceList);
    }
}
