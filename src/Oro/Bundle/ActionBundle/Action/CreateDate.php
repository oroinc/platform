<?php

namespace Oro\Bundle\ActionBundle\Action;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Action\Action\AbstractDateAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Action create DateTime object based on date string
 */
class CreateDate extends AbstractDateAction
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    public function __construct(ContextAccessor $contextAccessor, LocaleSettings $localeSettings)
    {
        parent::__construct($contextAccessor);

        $this->localeSettings = $localeSettings;
    }

    /**
     * @param mixed $context
     *
     * @return \DateTime
     */
    #[\Override]
    protected function createDateTime($context)
    {
        $fullDate = new \DateTime($this->getOption($this->options, 'date'), new \DateTimeZone('UTC'));

        return new \DateTime($fullDate->format('Y-m-d'), new \DateTimeZone('UTC'));
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options['date'])) {
            // as a default value should be used local timezone date
            $localDate = new \DateTime('', new \DateTimeZone($this->localeSettings->getTimeZone()));
            $options['date'] = $localDate->format('Y-m-d');
        } elseif (!is_string($options['date'])) {
            throw new InvalidParameterException(
                sprintf('Option "date" must be a string, %s given.', $this->getClassOrType($options['date']))
            );
        }

        return parent::initialize($options);
    }
}
