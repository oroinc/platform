<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FormType extends AbstractType
{
    /** @var ConfigSubscriber */
    protected $subscriber;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ConfigSubscriber   $subscriber
     * @param ContainerInterface $container
     */
    public function __construct(ConfigSubscriber $subscriber, ContainerInterface $container)
    {
        $this->subscriber = $subscriber;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);

        $blockConfig = array_shift($options['block_config']);
        if (!empty($blockConfig['configurator'])) {
            foreach ((array)$blockConfig['configurator'] as $configurator) {
                call_user_func($this->getCallback($configurator), $builder, $options);
            }
        }
        if (!empty($blockConfig['handler'])) {
            $handlers = (array)$blockConfig['handler'];
            $builder->setAttribute(
                'handler',
                function (ConfigManager $manager, ConfigChangeSet $changeSet, Form $form) use ($handlers) {
                    foreach ($handlers as $handler) {
                        call_user_func($this->getCallback($handler), $manager, $changeSet, $form);
                    }
                }
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_config_form_type';
    }

    /**
     * @param string $definition Can be "SomeClass::someMethod" or "@some_service::someMethod"
     *
     * @return callable
     */
    protected function getCallback($definition)
    {
        if (!is_string($definition)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected argument of type "string", "%s" given.',
                    is_object($definition) ? get_class($definition) : gettype($definition)
                )
            );
        }

        if (0 === strpos($definition, '@')) {
            $delimiterPos = strpos($definition, '::');
            if (false !== $delimiterPos) {
                $callback = [
                    $this->container->get(substr($definition, 1, $delimiterPos - 1)),
                    substr($definition, $delimiterPos + 2)
                ];
                if (is_callable($callback)) {
                    return $callback;
                }
            }
        } elseif (is_callable($definition)) {
            return $definition;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Expected that "%s" is a callable.',
                $definition
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['valid'] = $this->isFormValid($form);
    }

    /**
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isFormValid(FormInterface $form)
    {
        if ($form->isSubmitted() && !$form->isValid() && $form->getErrors()->count()) {
            return false;
        }

        $isChildValid = true;

        foreach ($form as $child) {
            if ($child->isSubmitted() && !$child->isValid() && $child->getErrors(true)->count()) {
                $isChildValid = false;
            }
        }

        return $isChildValid;
    }
}
