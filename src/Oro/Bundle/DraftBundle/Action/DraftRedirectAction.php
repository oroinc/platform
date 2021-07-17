<?php

namespace Oro\Bundle\DraftBundle\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Responsible for redirect to entity page.
 */
class DraftRedirectAction extends AbstractAction
{
    private const REDIRECT_PATH = 'redirectUrl';
    private const OPTION_KEY_ROUTE = 'route';
    private const OPTION_KEY_SOURCE = 'source';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        ContextAccessor $contextAccessor,
        ConfigManager $configManager,
        RouterInterface $router
    ) {
        parent::__construct($contextAccessor);
        $this->configManager = $configManager;
        $this->router = $router;
    }

    public function initialize(array $options): DraftRedirectAction
    {
        $this->options = $this->getOptionResolver()->resolve($options);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context): void
    {
        $this->contextAccessor->setValue($context, new PropertyPath(self::REDIRECT_PATH), $this->getUrl($context));
    }

    /**
     * @param ActionData $context
     *
     * @return string
     */
    private function getRoute($context): string
    {
        $object = $this->getSource($context);
        $metadata = $this->configManager->getEntityMetadata(ClassUtils::getRealClass($object));

        return $this->contextAccessor->getValue($metadata, $this->options[self::OPTION_KEY_ROUTE]);
    }

    private function getRouteParameters($context): array
    {
        $object = $this->getSource($context);

        return ['id' => $object->getId()];
    }

    private function getUrl($context): string
    {
        $route = $this->getRoute($context);
        $routeParameters = $this->getRouteParameters($context);

        return $this->router->generate($route, $routeParameters);
    }

    private function getSource($context): DraftableInterface
    {
        $source = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_SOURCE]);
        if ($source instanceof DraftableInterface) {
            return $source;
        }

        throw new \LogicException('Parameter \'source\' must implement DraftableInterface');
    }

    private function getOptionResolver(): OptionsResolver
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired(self::OPTION_KEY_SOURCE);
        $optionResolver->setRequired(self::OPTION_KEY_ROUTE);
        $optionResolver->setAllowedTypes(self::OPTION_KEY_SOURCE, ['object', PropertyPathInterface::class]);
        $optionResolver->setAllowedTypes(self::OPTION_KEY_ROUTE, ['object', PropertyPathInterface::class]);

        return $optionResolver;
    }
}
