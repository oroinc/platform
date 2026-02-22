<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'placeholder',
                [$this, 'renderPlaceholder'],
                ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['html']]
            )
        ];
    }

    /**
     * Render placeholder by name
     *
     * @param Environment       $environment
     * @param array             $context
     * @param string            $name
     * @param array             $variables
     * @param array             $attributes  Supported attributes:
     *                                       'delimiter' => string
     *
     * @return string|array
     */
    public function renderPlaceholder(
        Environment $environment,
        array $context,
        $name,
        array $variables = [],
        array $attributes = []
    ) {
        return implode(
            $attributes['delimiter'] ?? '',
            $this->getPlaceholderData($environment, $name, array_merge($context, $variables))
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
    private function renderItemContent(Environment $environment, array $item, array $variables)
    {
        if (isset($item['data']) || array_key_exists('data', $item)) {
            $variables['data'] = $item['data'];
        }

        if (isset($item['template'])) {
            return $environment->render($item['template'], $variables);
        }

        if (isset($item['action'])) {
            $query = [];
            $request = $this->getRequestStack()->getCurrentRequest();
            if (null !== $request) {
                $query = $request->query->all();
            }

            return $this->getFragmentHandler()->render(
                HttpKernelExtension::controller($item['action'], $variables, $query)
            );
        }

        throw new \RuntimeException(\sprintf(
            'Cannot render placeholder item with keys "%s". Expects "template" or "action" key.',
            implode('", "', $item)
        ));
    }

    /**
     * @param Environment       $environment
     * @param string            $name
     * @param array             $variables
     *
     * @return array
     */
    private function getPlaceholderData(Environment $environment, $name, $variables)
    {
        $result = [];
        $items = $this->getPlaceholderProvider()->getPlaceholderItems($name, $variables);
        foreach ($items as $item) {
            $result[] = $this->renderItemContent($environment, $item, $variables);
        }

        return $result;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            PlaceholderProvider::class,
            FragmentHandler::class,
            RequestStack::class
        ];
    }

    private function getPlaceholderProvider(): PlaceholderProvider
    {
        return $this->container->get(PlaceholderProvider::class);
    }

    private function getFragmentHandler(): FragmentHandler
    {
        return $this->container->get(FragmentHandler::class);
    }

    private function getRequestStack(): RequestStack
    {
        return $this->container->get(RequestStack::class);
    }
}
