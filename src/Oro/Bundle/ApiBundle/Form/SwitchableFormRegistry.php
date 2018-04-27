<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactoryInterface;

/**
 * The form registry is used to switch between default forms that are used on UI and Data API forms.
 * Unfortunately we have to use inheritance instead of aggregation because
 * some 3-rd party bundles can use FormRegistry instead of FormRegistryInterface.
 * An example of such usages is A2lix\TranslationFormBundle\TranslationForm\TranslationForm.
 */
class SwitchableFormRegistry extends FormRegistry implements FormExtensionSwitcherInterface
{
    public const DEFAULT_EXTENSION = 'default';
    public const API_EXTENSION     = 'api';

    /** @var SwitchableDependencyInjectionExtension */
    protected $extension;

    /** @var FormExtensionState */
    protected $extensionState;

    /** @var int */
    private $switchCounter = 0;

    /**
     * @param FormExtensionInterface[]         $extensions
     * @param ResolvedFormTypeFactoryInterface $resolvedTypeFactory
     * @param FormExtensionState               $extensionState
     */
    public function __construct(
        array $extensions,
        ResolvedFormTypeFactoryInterface $resolvedTypeFactory,
        FormExtensionState $extensionState
    ) {
        parent::__construct($extensions, $resolvedTypeFactory);

        if (\count($extensions) !== 1) {
            throw new \InvalidArgumentException('Expected only one form extension.');
        }
        $this->extension = \reset($extensions);
        if (!$this->extension instanceof SwitchableDependencyInjectionExtension) {
            throw new \InvalidArgumentException(\sprintf(
                'Expected type of form extension is "%s", "%s" given.',
                SwitchableDependencyInjectionExtension::class,
                \get_class($this->extension)
            ));
        }
        $this->extensionState = $extensionState;
    }

    /**
     * {@inheritdoc}
     */
    public function switchToDefaultFormExtension()
    {
        if ($this->switchCounter > 0) {
            $this->switchCounter--;
            if (0 === $this->switchCounter) {
                $this->switchFormExtension(self::DEFAULT_EXTENSION);
                $this->extensionState->switchToDefaultFormExtension();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function switchToApiFormExtension()
    {
        if (0 === $this->switchCounter) {
            $this->switchFormExtension(self::API_EXTENSION);
            $this->extensionState->switchToApiFormExtension();
        }
        $this->switchCounter++;
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
     * {@inheritdoc}
     */
    public function getType($name)
    {
        // prevent using of not registered in API form types
        if ($this->extensionState->isApiFormExtensionActivated()) {
            $isKnownType = false;
            $extensions = $this->getExtensions();
            foreach ($extensions as $extension) {
                if ($extension->hasType($name)) {
                    $isKnownType = true;
                    break;
                }
            }
            if (!$isKnownType) {
                throw new InvalidArgumentException(\sprintf(
                    'The form type "%s" is not configured to be used in Data API.',
                    $name
                ));
            }
        }

        return parent::getType($name);
    }

    /**
     * @param string $propertyName
     * @param mixed  $value
     */
    private function setPrivatePropertyValue($propertyName, $value)
    {
        $r = new \ReflectionClass(FormRegistry::class);
        if (!$r->hasProperty($propertyName)) {
            throw new \RuntimeException(\sprintf('The "%s" property does not exist.', $propertyName));
        }
        $p = $r->getProperty($propertyName);
        $p->setAccessible(true);
        $p->setValue($this, $value);
    }
}
