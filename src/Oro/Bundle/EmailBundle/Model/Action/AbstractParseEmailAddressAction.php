<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Provides common functionality for actions that parse email addresses.
 *
 * This base class handles the extraction and parsing of email addresses from strings,
 * using the {@see EmailAddressHelper} to perform the actual parsing. Subclasses should implement
 * specific parsing logic to extract different parts of email addresses (e.g., name, email).
 */
abstract class AbstractParseEmailAddressAction extends AbstractAction
{
    /** @var string */
    protected $address;

    /** @var string */
    protected $attribute;

    /** @var EmailAddressHelper */
    protected $addressHelper;

    public function __construct(ContextAccessor $contextAccessor, EmailAddressHelper $addressHelper)
    {
        parent::__construct($contextAccessor);
        $this->addressHelper = $addressHelper;
    }

    /**
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function initialize(array $options)
    {
        if (!isset($options['attribute']) && !isset($options[0])) {
            throw new InvalidParameterException('Attribute must be defined.');
        }

        if (!isset($options['email_address']) && !isset($options[1])) {
            throw new InvalidParameterException('Email address must be defined.');
        }

        $this->attribute = isset($options['attribute']) ? $options['attribute'] : $options[0];
        $this->address = isset($options['email_address']) ? $options['email_address'] : $options[1];
    }
}
