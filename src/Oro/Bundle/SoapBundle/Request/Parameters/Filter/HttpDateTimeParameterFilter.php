<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

class HttpDateTimeParameterFilter implements ParameterFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter($rawValue, $operator)
    {
        // datetime value hack due to the fact that some clients pass + encoded as %20 and not %2B,
        // so it becomes space on symfony side due to parse_str php function in HttpFoundation\Request
        $value = str_replace(' ', '+', $rawValue);

        // The timezone is ignored when DateTime value specifies a timezone (e.g. 2010-01-28T15:00:00+02:00)
        // TODO: should be fixed in BAP-8710. Need to use timezone from system config instead of UTC.
        return new \DateTime($value, new \DateTimeZone('UTC'));
    }
}
