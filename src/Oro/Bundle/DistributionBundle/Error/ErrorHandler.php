<?php

namespace Oro\Bundle\DistributionBundle\Error;

use Symfony\Component\ErrorHandler\ErrorHandler as BaseErrorHandler;

/**
 * Silencing error messages for known unresolvable issues
 */
class ErrorHandler extends BaseErrorHandler
{
    public static function register(BaseErrorHandler $handler = null, bool $replace = true): BaseErrorHandler
    {
        $errorLevel = error_reporting();
        // Silence all PHP deprecation notices as many of them are triggered all over the ORO and vendor code
        error_reporting($errorLevel & ~\E_DEPRECATED);

        return parent::register($handler, $replace);
    }

    /**
     * {@inheritdoc}
     */
    public function handleError(int $type, string $message, string $file, int $line): bool
    {
        if (error_reporting() !== 0) {
            // silence warning from php_network_getaddresses due to BAP-3979
            if (str_contains($message, 'php_network_getaddresses')) {
                return true;
            }

            // silence deprecation from ReflectionType::__toString()
            if (str_contains($message, 'Function ReflectionType::__toString() is deprecated')
                || str_contains($message, 'a ? b : c ? d : e')
            ) {
                return true;
            }
        }

        return parent::handleError($type, $message, $file, $line);
    }
}
