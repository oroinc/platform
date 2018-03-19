<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PlaceholderExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_placeholder';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }

    /**
     * @return PlaceholderProvider
     */
    protected function getPlaceholderProvider()
    {
        return $this->container->get('oro_ui.placeholder.provider');
    }

    /**
     * @return Request|null
     */
    protected function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'placeholder',
                [$this, 'renderPlaceholder'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            )
        ];
    }

    /**
     * Render placeholder by name
     *
     * @param \Twig_Environment $environment ,
     * @param string            $name
     * @param array             $variables
     * @param array             $attributes  Supported attributes:
     *                                       'delimiter' => string
     *
     * @return string|array
     */
    public function renderPlaceholder(
        \Twig_Environment $environment,
        $name,
        array $variables = [],
        array $attributes = []
    ) {
        return implode(
            isset($attributes['delimiter']) ? $attributes['delimiter'] : '',
            $this->getPlaceholderData($environment, $name, $variables)
        );
    }

    /**
     * Renders the given item.
     *
     * @param \Twig_Environment $environment
     * @param array             $item
     * @param array             $variables
     *
     * @return string
     *
     * @throws \RuntimeException If placeholder cannot be rendered.
     */
    protected function renderItemContent(\Twig_Environment $environment, array $item, array $variables)
    {
        if (isset($item['data']) || array_key_exists('data', $item)) {
            $variables['data'] = $item['data'];
        }

        if (isset($item['template'])) {
            return $environment->render($item['template'], $variables);
        }

        if (isset($item['action'])) {
            $query = [];
            $request = $this->getRequest();
            if (null !== $request) {
                $query = $request->query->all();
            }

            return $this->container->get('fragment.handler')->render(
                HttpKernelExtension::controller($item['action'], $variables, $query)
            );
        }

        throw new \RuntimeException(
            sprintf(
                'Cannot render placeholder item with keys "%s". Expects "template" or "action" key.',
                implode('", "', $item)
            )
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param string            $name
     * @param array             $variables
     *
     * @return array
     */
    protected function getPlaceholderData(\Twig_Environment $environment, $name, $variables)
    {
        $result = [];

        $items = $this->getPlaceholderProvider()->getPlaceholderItems($name, $variables);
        foreach ($items as $item) {
            $result[] = $this->renderItemContent($environment, $item, $variables);
        }

        return $result;
    }
}
