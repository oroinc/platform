<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render placeholders:
 *   - placeholder
 */
class PlaceholderExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'placeholder',
                [$this, 'renderPlaceholder'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            )
        ];
    }

    /**
     * Render placeholder by name
     *
     * @param Environment       $environment
     * @param string            $name
     * @param array             $variables
     * @param array             $attributes  Supported attributes:
     *                                       'delimiter' => string
     *
     * @return string|array
     */
    public function renderPlaceholder(
        Environment $environment,
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
     * @param Environment       $environment
     * @param array             $item
     * @param array             $variables
     *
     * @return string
     *
     * @throws \RuntimeException If placeholder cannot be rendered.
     */
    protected function renderItemContent(Environment $environment, array $item, array $variables)
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

            return $this->getFragmentHandler()->render(
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
     * @param Environment       $environment
     * @param string            $name
     * @param array             $variables
     *
     * @return array
     */
    protected function getPlaceholderData(Environment $environment, $name, $variables)
    {
        $result = [];

        $items = $this->getPlaceholderProvider()->getPlaceholderItems($name, $variables);
        foreach ($items as $item) {
            $result[] = $this->renderItemContent($environment, $item, $variables);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_ui.placeholder.provider' => PlaceholderProvider::class,
            'fragment.handler' => FragmentHandler::class,
            RequestStack::class,
        ];
    }

    protected function getPlaceholderProvider(): PlaceholderProvider
    {
        return $this->container->get('oro_ui.placeholder.provider');
    }

    protected function getFragmentHandler(): FragmentHandler
    {
        return $this->container->get('fragment.handler');
    }

    protected function getRequest(): ?Request
    {
        return $this->container->get(RequestStack::class)->getCurrentRequest();
    }
}
