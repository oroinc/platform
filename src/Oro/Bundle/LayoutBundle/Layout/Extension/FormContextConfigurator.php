<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

/**
 * Transforms form related context variables to the appropriate objects.
 */
class FormContextConfigurator implements ContextConfiguratorInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefined(['form'])
            ->setAllowedTypes(['form' => ['null', 'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface']]);

        $form = $context->getOr('form');
        if (null === $form || $form instanceof FormAccessorInterface) {
            return;
        }

        if (is_string($form)) {
            $form = new DependencyInjectionFormAccessor(
                $this->container,
                $form,
                $this->extractFormAction($context),
                $this->extractFormMethod($context),
                $this->extractFormEnctype($context)
            );
            $context->set('form', $form);
        } elseif ($form instanceof FormInterface) {
            $form = new FormAccessor(
                $form,
                $this->extractFormAction($context),
                $this->extractFormMethod($context),
                $this->extractFormEnctype($context)
            );
            $context->set('form', $form);
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The "form" must be a string, "%s" or "%s", but "%s" given.',
                    'Symfony\Component\Form\FormInterface',
                    'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface',
                    $this->getTypeName($form)
                )
            );
        }
    }

    /**
     * @param ContextInterface $context
     *
     * @return FormAction|null
     */
    protected function extractFormAction(ContextInterface $context)
    {
        $formAction = null;
        if ($context->has('form_action')) {
            $formAction = $context->get('form_action');
            if (is_string($formAction)) {
                $formAction = FormAction::createByPath($formAction);
            } elseif (!$formAction instanceof FormAction) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The "form_action" must be a string or instance of "%s", but "%s" given.',
                        'Oro\Bundle\LayoutBundle\Layout\Form\FormAction',
                        $this->getTypeName($formAction)
                    )
                );
            }
            $context->remove('form_action');
        } elseif ($context->has('form_route_name')) {
            $routeName = $context->get('form_route_name');
            if (!is_string($routeName)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The "form_route_name" must be a string, but "%s" given.',
                        $this->getTypeName($routeName)
                    )
                );
            }
            $routeParams = $context->has('form_route_parameters')
                ? $context->get('form_route_parameters')
                : [];
            $formAction  = FormAction::createByRoute($routeName, $routeParams);
            $context->remove('form_route_name');
            $context->remove('form_route_parameters');
        }

        return $formAction;
    }

    /**
     * @param ContextInterface $context
     *
     * @return string|null
     */
    protected function extractFormMethod(ContextInterface $context)
    {
        $formMethod = null;
        if ($context->has('form_method')) {
            $formMethod = $context->get('form_method');
            if (!is_string($formMethod)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The "form_method" must be a string, but "%s" given.',
                        $this->getTypeName($formMethod)
                    )
                );
            }
            $context->remove('form_method');
        }

        return $formMethod;
    }

    /**
     * @param ContextInterface $context
     *
     * @return string|null
     */
    protected function extractFormEnctype(ContextInterface $context)
    {
        $formEnctype = null;
        if ($context->has('form_enctype')) {
            $formEnctype = $context->get('form_enctype');
            if (!is_string($formEnctype)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The "form_enctype" must be a string, but "%s" given.',
                        $this->getTypeName($formEnctype)
                    )
                );
            }
            $context->remove('form_enctype');
        }

        return $formEnctype;
    }

    /**
     * @param mixed $val
     *
     * @return string
     */
    protected function getTypeName($val)
    {
        return is_object($val) ? get_class($val) : gettype($val);
    }
}
