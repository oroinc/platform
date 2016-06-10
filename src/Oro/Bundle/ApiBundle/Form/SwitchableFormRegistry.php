<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactoryInterface;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;

/**
 * Unfortunately we have to use inheritance instead of aggregation because there are
 * 3-rd party bundles that use FormRegistry instead of FormRegistryInterface.
 * For example:
 * @see \A2lix\TranslationFormBundle\TranslationForm\TranslationForm
 */
class SwitchableFormRegistry extends FormRegistry implements FormExtensionSwitcherInterface
{
    const DEFAULT_EXTENSION = 'default';
    const API_EXTENSION     = 'api';

    /** @var SwitchableDependencyInjectionExtension */
    protected $extension;

    /**
     * @param FormExtensionInterface[]         $extensions
     * @param ResolvedFormTypeFactoryInterface $resolvedTypeFactory
     */
    public function __construct(array $extensions, ResolvedFormTypeFactoryInterface $resolvedTypeFactory)
    {
        parent::__construct($extensions, $resolvedTypeFactory);

        if (count($extensions) !== 1) {
            throw new \InvalidArgumentException('Expected only one form extension.');
        }
        $this->extension = reset($extensions);
        if (!$this->extension instanceof SwitchableDependencyInjectionExtension) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected type of form extension is "%s", "%s" given.',
                    'Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension',
                    get_class($this->extension)
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function switchToDefaultFormExtension()
    {
        $this->switchFormExtension(self::DEFAULT_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToApiFormExtension()
    {
        $this->switchFormExtension(self::API_EXTENSION);
    }

    /**
     * @param string $extensionName
     */
    protected function switchFormExtension($extensionName)
    {
        $this->extension->switchFormExtension($extensionName);
        // clear local cache
        // unfortunately $types and $guesser property are private and there is no other way
        // to reset them except to use the reflection
        $this->setPrivatePropertyValue('types', []);
        $this->setPrivatePropertyValue('guesser', false);
    }

    /**
     * @param string $propertyName
     * @param mixed  $value
     */
    protected function setPrivatePropertyValue($propertyName, $value)
    {
        $r = new \ReflectionClass('Symfony\Component\Form\FormRegistry');
        if (!$r->hasProperty($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property does not exist.', $propertyName));
        }
        $p = $r->getProperty($propertyName);
        $p->setAccessible(true);
        $p->setValue($this, $value);
    }
}
