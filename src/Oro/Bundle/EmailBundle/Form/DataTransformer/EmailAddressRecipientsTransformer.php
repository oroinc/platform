<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms form value between array of full email addresses and string of base64 encoded full email addresses.
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

        return EmailRecipientsHelper::extractFormRecipientIds($value);
    }
}
