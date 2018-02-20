<?php

namespace Oro\Bundle\FormBundle\Form\Twig;

use Oro\Bundle\FormBundle\Form\Builder\DataBlockBuilder;
use Symfony\Component\Form\FormView;

class DataBlockRenderer
{
    /**
     * @param \Twig_Environment $env
     * @param array             $context
     * @param FormView          $form
     * @param string            $formVariableName
     *
     * @return array
     */
    public function render(\Twig_Environment $env, $context, FormView $form, $formVariableName = 'form')
    {
        // remember current loader
        $originalLoader = $env->getLoader();

        // replace the loader
        $env->setLoader(new \Twig_Loader_Chain(array($originalLoader, new \Twig_Loader_String())));

        // build blocks
        $builder = new DataBlockBuilder(new TwigTemplateRenderer($env, $context), $formVariableName);
        $result  = $builder->build($form);

        // restore the original loader
        $env->setLoader($originalLoader);

        return $result->toArray();
    }
}
