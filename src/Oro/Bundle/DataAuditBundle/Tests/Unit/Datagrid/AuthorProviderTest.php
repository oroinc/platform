<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataAuditBundle\Datagrid\AuthorProvider;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;

class AuthorProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorProvider */
    private $provider;

    protected function setUp()
    {
        $this->provider = new AuthorProvider(new StubTranslator());
    }

    public function testGetAuthorEmpty()
    {
        $callable = $this->provider->getAuthor('gridName', 'keyName', []);

        $this->assertSame(null, call_user_func_array($callable, [new ResultRecord([])]));
    }

    public function testGetAuthorImpresonationOnly()
    {
        $callable = $this->provider->getAuthor('gridName', 'keyName', []);

        $this->assertSame(
            '[trans]oro.dataaudit.datagrid.author_impersonation[/trans]',
            call_user_func_array($callable, [new ResultRecord(['impersonation' => new Impersonation()])])
        );
    }

    public function testGetAuthorOnly()
    {
        $callable = $this->provider->getAuthor('gridName', 'keyName', []);

        $this->assertSame(
            'John Doe - jdoe@example.com',
            call_user_func_array($callable, [new ResultRecord(['author' => 'John Doe - jdoe@example.com'])])
        );
    }

    public function testGetAuthorAndImpresonation()
    {
        $callable = $this->provider->getAuthor('gridName', 'keyName', []);

        $this->assertSame(
            'John Doe - jdoe@example.com [trans]oro.dataaudit.datagrid.author_impersonation[/trans]',
            call_user_func_array(
                $callable,
                [new ResultRecord(['author' => 'John Doe - jdoe@example.com', 'impersonation' => new Impersonation()])]
            )
        );
    }
}
