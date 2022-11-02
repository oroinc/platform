<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\EmailBundle\Datagrid\Extension\MassAction\Actions\MarkReadMassAction;
use Oro\Bundle\EmailBundle\Datagrid\Extension\MassAction\Actions\MarkUnreadMassAction;
use Oro\Bundle\EmailBundle\Datagrid\Extension\MassAction\MarkMassActionHandler;

class MarkMassActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MarkReadMassAction */
    private $readAction;

    /** @var MarkUnreadMassAction */
    private $unreadAction;

    /** @var ActionConfiguration */
    private $configuration;

    protected function setUp(): void
    {
        $this->configuration = ActionConfiguration::createNamed(
            'test',
            [
                'entity_name' => 'test',
                'data_identifier' => 'test'
            ]
        );
    }

    public function testMarkRead()
    {
        $this->readAction = new MarkReadMassAction();
        $this->readAction->setOptions($this->configuration);

        $options = $this->readAction->getOptions();
        $this->assertEquals(MarkMassActionHandler::MARK_READ, $options->offsetGet('mark_type'));
    }

    public function testMarkUnread()
    {
        $this->configuration->offsetUnset('mark_type');

        $this->unreadAction = new MarkUnreadMassAction();
        $this->unreadAction->setOptions($this->configuration);

        $options = $this->unreadAction->getOptions();
        $this->assertEquals(MarkMassActionHandler::MARK_UNREAD, $options->offsetGet('mark_type'));
    }
}
