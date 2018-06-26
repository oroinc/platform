<?php

/*
 * This file is part of the GenemuFormBundle package.
 *
 * (c) Olivier Chauvel <olivier@generation-multiple.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\Select2ArrayToStringTransformerDecorator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Select2Type extends AbstractType
{
    const HIDDEN_TYPE = 'Symfony\Component\Form\Extension\Core\Type\HiddenType';

    /**
     * @var string
     */
    private $parentForm;

    /**
     * @var string
     */
    private $blockPrefix;

    /**
     * @param $parentForm
     * @param $blockPrefix
     */
    public function __construct($parentForm, $blockPrefix)
    {
        $this->parentForm = $parentForm;
        $this->blockPrefix = $blockPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->parentForm === self::HIDDEN_TYPE) {
            $this->addHiddenTypeTransformer($builder, $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['configs'] = $options['configs'];

        $this->addSelect2BlockPrefix($view);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'configs' => [],
            'transformer' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parentForm;
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
        return $this->blockPrefix;
    }

    /**
     * @param FormView $view
     */
    private function addSelect2BlockPrefix(FormView $view)
    {
        $blockPrefixes = $view->vars['block_prefixes'];
        $position = array_search($this->getName(), $blockPrefixes);

        if ($position) {
            array_splice($blockPrefixes, $position, 0, 'oro_select2');
            $view->vars['block_prefixes'] = $blockPrefixes;
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    private function addHiddenTypeTransformer(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['configs']['multiple'])) {
            $builder->addViewTransformer(
                new Select2ArrayToStringTransformerDecorator(new ArrayToStringTransformer(',', true))
            );
        } elseif (null !== $options['transformer']) {
            $builder->addModelTransformer($options['transformer']);
        }
    }
}
