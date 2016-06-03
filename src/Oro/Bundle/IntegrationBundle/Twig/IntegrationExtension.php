<?php

namespace Oro\Bundle\IntegrationBundle\Twig;

use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;

use Twig_Extension;
use Twig_SimpleFunction;

use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;

class IntegrationExtension extends Twig_Extension
{
    const DEFAULT_THEME = 'OroIntegrationBundle:Form:fields.html.twig';

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('oro_integration_themes', [$this, 'getThemes']),
            new Twig_SimpleFunction('oro_integration_is_switch_enabled', [$this, 'isSwitchEnabled']),
            new Twig_SimpleFunction('oro_integration_is_delete_enabled', [$this, 'isDeleteEnabled']),
        ];
    }

    /**
     * @param FormView $view
     *
     * @return array
     */
    public function getThemes(FormView $view)
    {
        $themes = [static::DEFAULT_THEME];
        if (!$this->dispatcher->hasListeners(LoadIntegrationThemesEvent::NAME)) {
            return $themes;
        }

        $event = new LoadIntegrationThemesEvent($view, $themes);
        $this->dispatcher->dispatch(LoadIntegrationThemesEvent::NAME, $event);

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
    public function getName()
    {
        return 'oro_integration';
    }
}
