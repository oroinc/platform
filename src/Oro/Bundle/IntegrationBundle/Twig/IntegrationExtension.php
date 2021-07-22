<?php

namespace Oro\Bundle\IntegrationBundle\Twig;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions used for rendering of integration settings forms:
 *   - oro_integration_themes
 *   - oro_integration_is_switch_enabled
 *   - oro_integration_is_delete_enabled
 */
class IntegrationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const DEFAULT_THEME = '@OroIntegration/Form/fields.html.twig';

    private ContainerInterface $container;

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
            new TwigFunction('oro_integration_themes', [$this, 'getThemes']),
            new TwigFunction('oro_integration_is_switch_enabled', [$this, 'isSwitchEnabled']),
            new TwigFunction('oro_integration_is_delete_enabled', [$this, 'isDeleteEnabled']),
        ];
    }

    /**
     * @param FormView $view
     *
     * @return array
     */
    public function getThemes(FormView $view)
    {
        $themes = [self::DEFAULT_THEME];
        $eventDispatcher = $this->getEventDispatcher();
        if (!$eventDispatcher->hasListeners(LoadIntegrationThemesEvent::NAME)) {
            return $themes;
        }

        $event = new LoadIntegrationThemesEvent($view, $themes);
        $eventDispatcher->dispatch($event, LoadIntegrationThemesEvent::NAME);

        return $event->getThemes();
    }

    /**
     * @param int $editMode
     *
     * @return bool
     */
    public function isSwitchEnabled($editMode)
    {
        return EditModeUtils::isSwitchEnableAllowed($editMode);
    }

    /**
     * @param int $editMode
     *
     * @return bool
     */
    public function isDeleteEnabled($editMode)
    {
        // edit mode which allows to edit integration allows to delete it.
        return EditModeUtils::isEditAllowed($editMode);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            EventDispatcherInterface::class,
        ];
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(EventDispatcherInterface::class);
    }
}
