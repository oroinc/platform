<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

/**
 * {@inheritdoc}
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

        $string = EmailRecipientsHelper::prepareFormRecipientIds($value);

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $array = EmailRecipientsHelper::extractFormRecipients($value);

        return $array;
    }
}
