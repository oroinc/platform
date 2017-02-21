<?php

namespace Oro\Bundle\ImportExportBundle\Async;

class Topics
{
    const PRE_CLI_IMPORT = 'oro.importexport.pre_cli_import';
    const CLI_IMPORT = 'oro.importexport.cli_import';
    /** @deprecated since 2.1, will be removed in 2.3, use CLI_IMPORT instead */
    const IMPORT_CLI = 'oro.importexport.cli_import';
    const PRE_HTTP_IMPORT = 'oro.importexport.pre_http_import';
    const HTTP_IMPORT = 'oro.importexport.http_import';
    const EXPORT = 'oro.importexport.export';
    const SEND_IMPORT_NOTIFICATION = 'oro.importexport.send_import_notification';
    /** todo: temporary solution, remove the processor and mark as deprecated after BAP-13215   */
    const SEND_IMPORT_ERROR_NOTIFICATION = 'oro.importexport.send_import_error_notification';

    /**
     * @deprecated (deprecated since 2.1 will be removed in 2.3, please use PRE_HTTP_IMPORT topic name instead.)
     */
    const IMPORT_HTTP_PREPARING = 'oro.importexport.import_http_preparing';
    /**
     * @deprecated (deprecated since 2.1 will be removed in 2.3, please use PRE_HTTP_IMPORT topic name instead.)
     */
    const IMPORT_HTTP_VALIDATION_PREPARING = 'oro.importexport.import_http_validation_preparing';
    /**
     * @deprecated (deprecated since 2.1 will be removed in 2.3, please use HTTP_IMPORT topic name instead.)
     */
    const IMPORT_HTTP_VALIDATION = 'oro.importexport.import_http_validation';
    /**
     * @deprecated (deprecated since 2.1 will be removed in 2.3, please use HTTP_IMPORT topic name instead.)
     */
    const IMPORT_HTTP = 'oro.importexport.import_http';
}
