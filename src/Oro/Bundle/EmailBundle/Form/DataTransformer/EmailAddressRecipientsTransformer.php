<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

/**
 * Transforms between array of ids and string of ids.
 */
class EmailAddressRecipientsTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        return EmailRecipientsHelper::prepareFormRecipientIds($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return EmailRecipientsHelper::extractFormRecipients($value);
    }
}
