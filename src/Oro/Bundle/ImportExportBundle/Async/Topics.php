<?php

namespace Oro\Bundle\ImportExportBundle\Async;

class Topics
{
    const IMPORT_CLI = 'oro.importexport.import_cli';
    const IMPORT_CLI_VALIDATION = 'oro.importexport.import_cli_validation';
    //Topic for split files on chunk and prepare for http import
    const IMPORT_HTTP_PREPARING = 'oro.importexport.import_http_preparing';
    //Topic for http import(part of import)
    const IMPORT_HTTP = 'oro.importexport.import_http';
    //Topic for split files on chunk and prepare for http validate import
    const IMPORT_HTTP_VALIDATION_PREPARING = 'oro.importexport.import_http_validation_preparing';
    //Topic for http validation chunk of import
    const IMPORT_HTTP_VALIDATION = 'oro.importexport.import_http_validation';
    const EXPORT = 'oro.importexport.export';
    const SEND_IMPORT_NOTIFICATION = 'oro.importexport.send_notification';
    /**
     * @deprecated Renamed in 2.1 to "oro.importexport.send_import_error_notification"
     */
    const SEND_IMPORT_ERROR_INFO = 'oro.importexport.send_error_info';
}
