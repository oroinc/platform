<?php

namespace Oro\Bundle\FormBundle\Form\Twig;

use Oro\Bundle\FormBundle\Form\Builder\DataBlockBuilder;
use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

/**
 * Renders a data blocks
 */
class DataBlockRenderer
{
    /**
     * @param Environment $env
     * @param array       $context
     * @param FormView    $form
     * @param string      $formVariableName
     *
     * @return array
     */
    public function render(Environment $env, $context, FormView $form, $formVariableName = 'form')
    {
        // remember current loader
        $originalLoader = $env->getLoader();

        // replace the loader
        $env->setLoader(new ChainLoader(array($originalLoader, new ArrayLoader())));

        // build blocks
        $builder = new DataBlockBuilder(new TwigTemplateRenderer($env, $context), $formVariableName);
        $result  = $builder->build($form);

        // restore the original loader
        $env->setLoader($originalLoader);

        return $result->toArray();
    }
}
