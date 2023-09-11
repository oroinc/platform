<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Sets submitted additional attributes to Email entity.
 */
class UpdateEmailAttributes implements ProcessorInterface
{
    private PropertyAccessorInterface $propertyAccessor;
    private EntityListener $emailEntityListener;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        EntityListener $emailEntityListener
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->emailEntityListener = $emailEntityListener;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $emailModel = $context->get(ReplaceEmailModelInContext::EMAIL_MODEL);
        if (!$emailModel instanceof EmailModel) {
            return;
        }

        $attributes = $emailModel->getAttributes();
        if (!$attributes) {
            return;
        }

        $email = $context->getData();
        if (!$email instanceof Email) {
            return;
        }

        foreach ($attributes as $name => $value) {
            try {
                $this->propertyAccessor->setValue($email, $name, $value);
            } catch (NoSuchPropertyException) {
                // ignore this exception to be able to add additional processors to handle custom submitted data
            }
        }

        if (isset($attributes['activityTargets'])) {
            $this->emailEntityListener->skipUpdateActivities($email);
        }
    }
}
