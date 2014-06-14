<?php

namespace Oro\Bundle\FormBundle\Twig;

use Oro\Bundle\FormBundle\Form\Twig\DataBlockRenderer;

class FormExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'form_data_blocks',
                array(new DataBlockRenderer(), 'render'),
                array('needs_context' => true, 'needs_environment' => true)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_form';
    }
}
