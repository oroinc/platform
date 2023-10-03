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
    public function render(Environment $env, $context, FormView $form, string $formVariableName = 'form'): array
    {
        // remember current loader
        $originalLoader = $env->getLoader();

        // replace the loader
        $env->setLoader(new ChainLoader(array($originalLoader, new ArrayLoader())));

        // build blocks
        $builder = new DataBlockBuilder($env, $context, $formVariableName);
        $result  = $builder->build($form);

        // restore the original loader
        $env->setLoader($originalLoader);

        return $result->toArray();
    }
}
