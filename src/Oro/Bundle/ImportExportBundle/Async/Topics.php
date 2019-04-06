<?php

namespace Oro\Bundle\ImportExportBundle\Async;

/**
 * ImportExportBundle related Message Queue Topics
 */
class Topics
{
    const PRE_IMPORT = 'oro.importexport.pre_import';
    const IMPORT = 'oro.importexport.import';
    const PRE_EXPORT = 'oro.importexport.pre_export';
    const EXPORT = 'oro.importexport.export';
    const POST_EXPORT = 'oro.importexport.post_export';
    const SEND_IMPORT_NOTIFICATION = 'oro.importexport.send_import_notification';
    const SAVE_IMPORT_EXPORT_RESULT = 'oro.importexport.save_import_export_result';

    /**
     * @deprecated since 3.1, will be removed in 4.0, please use PRE_IMPORT topic
     */
    const PRE_HTTP_IMPORT = 'oro.importexport.pre_http_import';

    /**
     * @deprecated since 3.1, will be removed in 4.0, please use IMPORT topic
     */
    const HTTP_IMPORT = 'oro.importexport.http_import';
}
