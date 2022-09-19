<?php

namespace Oro\Bundle\DraftBundle\Route;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Route\Router as BaseRouter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * The router that redirects response to edit new created draft after it was created from edit page.
 */
class Router extends BaseRouter
{
    private ConfigManager $configManager;

    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function redirect(mixed $context): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $redirectAction = $request->get(self::ACTION_PARAMETER);
        if ('save_as_draft' !== $redirectAction) {
            return parent::redirect($context);
        }

        $metadata = $this->configManager->getEntityMetadata(ClassUtils::getRealClass($context));

        return new RedirectResponse(
            $this->urlGenerator->generate($metadata->getRoute(), ['id' => $context->getId()])
        );
    }
}
