<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Doctrine\ORM\Configuration;

class LoggingConfiguration extends Configuration
{
    /**
     * Gets the ORM logger.
     *
     * @return OrmLogger
     */
    public function getOrmProfilingLogger()
    {
        return isset($this->_attributes['ormProfilingLogger'])
            ? $this->_attributes['ormProfilingLogger']
            : null;
    }

    /**
     * Sets the ORM logger.
     *
     * @param OrmLogger $logger
     */
    public function setOrmProfilingLogger(OrmLogger $logger)
    {
        $this->_attributes['ormProfilingLogger'] = $logger;
    }

    /**
     * Gets logging hydrators.
     *
     * @return array
     */
    public function getLoggingHydrators()
    {
        return isset($this->_attributes['loggingHydrators'])
            ? $this->_attributes['loggingHydrators']
            : [];
    }

    /**
     * Sets logging hydrators.
     *
     * @param array $hydrators
     */
    public function setLoggingHydrators(array $hydrators)
    {
        $this->_attributes['loggingHydrators'] = $hydrators;
    }
}
