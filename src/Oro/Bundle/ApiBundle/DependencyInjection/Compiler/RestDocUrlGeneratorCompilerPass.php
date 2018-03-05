<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets the default view to RestDocUrlGenerator service.
 */
class RestDocUrlGeneratorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_api.rest.doc_url_generator')
            ->replaceArgument(2, $this->getDefaultView($container));
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string|null
     */
    private function getDefaultView(ContainerBuilder $container): ?string
    {
        $defaultView = null;
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            if (\array_key_exists('default', $view) && $view['default']) {
                $defaultView = $name;
                break;
            }
        }

        return $defaultView;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getApiDocViews(ContainerBuilder $container): array
    {
        $config = DependencyInjectionUtil::getConfig($container);

        return $config['api_doc_views'];
    }
}
