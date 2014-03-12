<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Bridge\Twig\Extension\HttpKernelExtension;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;

class PlaceholderExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_placeholder';

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var PlaceholderProvider
     */
    protected $placeholder;

    /**
     * @var HttpKernelExtension
     */
    protected $kernelExtension;

    /**
     * @param \Twig_Environment $environment
     * @param PlaceholderProvider $placeholder
     * @param HttpKernelExtension $kernelExtension
     */
    public function __construct(
        \Twig_Environment $environment,
        PlaceholderProvider $placeholder,
        HttpKernelExtension $kernelExtension
    ) {
        $this->environment = $environment;
        $this->placeholder = $placeholder;
        $this->kernelExtension = $kernelExtension;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'placeholder' => new \Twig_Function_Method(
                $this,
                'renderPlaceholder',
                array(
                    'is_safe' => array('html')
                )
            )
        );
    }

    /**
     * Render placeholder by name
     *
     * @param string $name
     * @param array $variables
     * @param string $delimiter
     * @return string
     * @throws \RuntimeException If placeholder cannot be rendered.
     */
    public function renderPlaceholder($name, array $variables = array(), $delimiter = '')
    {
        $renderedBlocks = [];

        $items = $this->placeholder->getPlaceholderItems($name, $variables);

        foreach ($items as $block) {
            if (isset($block['template'])) {
                $renderedBlocks[] = $this->environment->render($block['template'], $variables);
            } elseif (isset($block['action'])) {
                /** @var HttpKernelExtension $kernelExtension */
                $renderedBlocks[] = $this->kernelExtension->renderFragment(
                    $this->kernelExtension->controller($block['action'], $variables)
                );
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot render placeholder item with keys "%s". Expects "template" or "action" key.',
                        implode('", "', $block)
                    )
                );
            }
        }

        return implode($delimiter, $renderedBlocks);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
