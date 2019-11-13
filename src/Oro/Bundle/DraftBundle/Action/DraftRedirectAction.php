<?php

namespace Oro\Bundle\DraftBundle\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Component\Action\Action\Redirect;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Responsible for redirect action after draft create.
 */
class DraftRedirectAction extends Redirect
{
    private const OPTION_KEY_TARGET = 'target';
    private const OPTION_KEY_ROUTE_PARAMETERS = 'route_parameters';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ConfigManager $configManager
     * @param RouterInterface $router
     * @param $redirectPath
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ConfigManager $configManager,
        RouterInterface $router,
        $redirectPath
    ) {
        parent::__construct($contextAccessor, $router, $redirectPath);
        $this->configManager = $configManager;
    }

    /**
     * @param array $options
     *
     * @return DraftRedirectAction
     */
    public function initialize(array $options): DraftRedirectAction
    {
        $this->getOptionResolver()->resolve($options);
        $this->urlAttribute = new PropertyPath($this->urlAttribute);
        $this->options = $options;

        return $this;
    }

    /**
     * @param ActionData $context
     *
     * @return string
     */
    protected function getRoute($context): string
    {
        $object = $this->contextAccessor->getValue($context, $this->options['target']);
        if ($object instanceof DraftableInterface) {
            $metadata = $this->configManager->getEntityMetadata(ClassUtils::getRealClass($object));

            return $metadata->routes['update'];
        }

        throw new \LogicException('Parameter must implement DraftableInterface');
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver(): OptionsResolver
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired(self::OPTION_KEY_TARGET);
        $optionResolver->setAllowedTypes(self::OPTION_KEY_TARGET, ['object', PropertyPathInterface::class]);
        $optionResolver->setRequired(self::OPTION_KEY_ROUTE_PARAMETERS);
        $optionResolver->setAllowedTypes(self::OPTION_KEY_ROUTE_PARAMETERS, ['array']);
        $optionResolver->setNormalizer(self::OPTION_KEY_ROUTE_PARAMETERS, function (Options $options, $value) {
            if (!array_key_exists('id', $value)) {
                throw new InvalidOptionsException('The required options "id" are missing.');
            }

            return $value;
        });

        return $optionResolver;
    }
}
