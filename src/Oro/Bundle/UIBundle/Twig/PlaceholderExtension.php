<?php

namespace Oro\Bundle\UIBundle\Twig;

class PlaceholderExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_placeholder';

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var array
     */
    protected $placeholders = [];

    /**
     * @param \Twig_Environment $environment
     * @param array             $placeholders
     */
    public function __construct(\Twig_Environment $environment, array $placeholders)
    {
        $this->environment = $environment;
        $this->placeholders = $placeholders;
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
                'renderPlaceholders',
                array(
                    'is_safe' => array('html')
                )
            )
        );
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param string $delimiter
     *
     * @return array
     */
    public function renderPlaceholders($name, array $parameters = [], $delimiter = '')
    {
        $renderedBlocks = [];
        if (isset($this->placeholders[$name]['items'])) {
            foreach ($this->placeholders[$name]['items'] as $block) {
                $renderedBlocks[] = $this->environment->render($block['template'], $parameters);
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
