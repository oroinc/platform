<?php

namespace Oro\Bundle\SoapBundle\Handler;

interface IncludeHandlerInterface
{
    const HEADER_INCLUDE     = 'X-Include';
    const HEADER_UNSUPPORTED = 'X-Include-Unsupported';
    const HEADER_UNKNOWN     = 'X-Include-Unknown';
    const DELIMITER          = ';';
    
    /**
     * Is handler object supports "include request"
     *
     * @param Context $context                                               Context contains response and request
     *                                                                       and also may contain some additional info
     *                                                                       (such as action name, query param etc..)
     *
     * @return bool
     */
    public function supports(Context $context);

    /**
     * Process "include request" and modify response object
     *
     * @param Context $context                                               Context contains response and request
     *                                                                       and also may contain some additional info
     *                                                                       (such as action name, query param etc..)
     *
     * @return void
     */
    public function handle(Context $context);
}
