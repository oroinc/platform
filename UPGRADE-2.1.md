UPGRADE FROM 2.0 to 2.1
========================

##DataGridBundle
 - Class `Oro\Bundle\DataGridBundle\Engine\Orm\PdoMysql\GroupConcat` was removed. Use `GroupConcat` from package `oro/doctrine-extensions` instead.

##SecurityBundle
- Service overriding in compiler pass was replaced by service decoration for next services:
    - `sensio_framework_extra.converter.doctrine.orm`
    - `security.acl.dbal.provider`
    - `security.acl.cache.doctrine`
    - `security.acl.voter.basic_permissions`
- Next container parameters were removed:
    - `oro_security.acl.voter.class`

##LayoutBundle
- Class `Oro\Bundle\LayoutBundle\DependencyInjection\CompilerOverrideServiceCompilerPass` was removed

##EmailBundle
- Added `Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface` and implemented it in `Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer`

##ImapBundle
- Updated `Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor::__construct()` signature to use `Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface`.