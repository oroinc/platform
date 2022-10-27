<?php

namespace Oro\Bundle\ActionBundle\Action;

use Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Resolves the url where the user should be redirected after the operation is completed.
 */
class ResolveDestinationPage extends AbstractAction
{
    /** @var DestinationPageResolver */
    protected $resolver;

    /** @var RequestStack */
    protected $requestStack;

    /** @var string */
    protected $destination;

    /** @var PropertyPathInterface */
    protected $entity;

    /** @var PropertyPathInterface */
    protected $attribute;

    public function __construct(
        ContextAccessor $contextAccessor,
        DestinationPageResolver $resolver,
        RequestStack $requestStack
    ) {
        parent::__construct($contextAccessor);

        $this->resolver = $resolver;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->destination = $this->getDestinationOption($options);
        $this->entity = $this->getEntityOption($options);
        $this->attribute = $this->getAttributeOption($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (null === ($request = $this->requestStack->getMainRequest())) {
            return;
        }

        $redirectUrl = $request->get(DestinationPageResolver::PARAM_ORIGINAL_URL);
        if ($this->destination !== DestinationPageResolver::DEFAULT_DESTINATION) {
            $entity = $this->contextAccessor->getValue($context, $this->entity);
            $redirectUrl = $this->resolver->resolveDestinationUrl($entity, $this->destination);
        }

        if ($redirectUrl) {
            $this->contextAccessor->setValue($context, $this->attribute, urldecode($redirectUrl));
        }
    }

    /**
     * @param array $options
     * @return string
     */
    protected function getDestinationOption(array $options)
    {
        $destination = DestinationPageResolver::DEFAULT_DESTINATION;
        if (isset($options['destination'])) {
            $destination = $options['destination'];
        }

        if (isset($options[0])) {
            $destination = $options[0];
        }

        return $destination;
    }

    /**
     * @param array $options
     * @return PropertyPath
     * @throws InvalidParameterException
     */
    protected function getEntityOption(array $options)
    {
        $entity = new PropertyPath('entity');
        if (isset($options['entity'])) {
            $entity = $options['entity'];
        }

        if (isset($options[1])) {
            $entity = $options[1];
        }

        if (!$entity instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Entity must be valid property definition.');
        }

        return $entity;
    }

    /**
     * @param array $options
     * @return PropertyPath
     * @throws InvalidParameterException
     */
    protected function getAttributeOption(array $options)
    {
        $attribute = new PropertyPath('redirectUrl');
        if (isset($options['attribute'])) {
            $attribute = $options['attribute'];
        }

        if (isset($options[2])) {
            $attribute = $options[2];
        }

        if (!$attribute instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        return $attribute;
    }
}
