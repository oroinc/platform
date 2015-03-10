<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Handler;

use Oro\Bundle\DistributionBundle\Handler\ErrorHandler;

class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected static $reportingType;

    public static function setUpBeforeClass()
    {
        self::$reportingType = ini_get('error_reporting');
    }

    public static function tearDownAfterClass()
    {
        ini_set('error_reporting', self::$reportingType);
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage Test error
     */
    public function testRegister()
    {
        ini_set('error_reporting', E_USER_NOTICE);
        ErrorHandler::register(E_ALL);
        ErrorHandler::register(E_ALL);
        trigger_error('Test error', E_USER_NOTICE);
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage Undefined index: not existing key
     */
    public function testHandleError()
    {
        ini_set('error_reporting', E_NOTICE);
        $testArray = [
            'existed key' => true
        ];

        ErrorHandler::register(E_ALL);
        $testArray['not existing key'];
    }

    public function testHandleErrorIgnoredErrorIfErrorsSuppressed()
    {
        ini_set('error_reporting', E_NOTICE);
        $testArray = [
            'existed key' => true
        ];

        ErrorHandler::register(E_ALL);
        @$testArray['not existing key'];
    }

    public function testHandleErrorIgnoredErrorIfErrorReportingNotIncludeHandledErrorTypes()
    {
        ini_set('error_reporting', E_WARNING);
        ErrorHandler::register(E_ALL);
        $testArray = [
            'existed key' => true
        ];
        $testArray['not existing key'];
    }
}
