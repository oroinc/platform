<?php
/**
 * LdapUserExportProcessor.php
 *
 * Project: crm-enterprise-dev
 * Author: Jakub Babiak <jakub@babiak.cz>
 * Created: 18/05/15 11:29
 */

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

class LdapUserExportProcessor extends ExportProcessor
{

}