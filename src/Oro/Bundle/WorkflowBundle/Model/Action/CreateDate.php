<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CreateDate extends AbstractDateAction
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @param ContextAccessor $contextAccessor
     * @param LocaleSettings $localeSettings
     */
    public function __construct(ContextAccessor $contextAccessor, LocaleSettings $localeSettings)
    {
        parent::__construct($contextAccessor);

        $this->localeSettings = $localeSettings;
    }

    /**
     * @return \DateTime
     */
    protected function createDateTime()
    {
        return new \DateTime(
            $this->getOption($this->options, 'date'),
            new \DateTimeZone('UTC')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['date'])) {
            // as a default value should be used local timezone date
            $localDate = new \DateTime(null, new \DateTimeZone($this->localeSettings->getTimeZone()));
            $options['date'] = $localDate->format('Y-m-d');
        } elseif (!is_string($options['date'])) {
            throw new InvalidParameterException(
                sprintf('Option "date" must be a string, %s given.', $this->getClassOrType($options['date']))
            );
        }

        return parent::initialize($options);
    }
}
