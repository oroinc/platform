<?php

namespace Oro\Bundle\UIBundle\Twig;

class BlockExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'render_block',
                [$this, 'renderBlock'],
                [
                    'needs_environment' => true,
                    'needs_context'     => true,
                    'is_safe'           => array('html')
                ]
            )
        );
    }

    /**
     * @param \Twig_Environment $env
     * @param array $context
     * @param string $template
     * @param string $block
     * @param array $extraContext
     * @return string
     */
    public function renderBlock(\Twig_Environment $env, $context, $template, $block, $extraContext = [])
    {
        /** @var \Twig_Template $template */
        $template = $env->loadTemplate($template);

        return $template->renderBlock($block, array_merge($context, $extraContext));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ui.block';
    }
}
