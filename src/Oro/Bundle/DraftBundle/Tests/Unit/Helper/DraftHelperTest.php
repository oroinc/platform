<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Helper;

use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DraftHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testIsSaveAsDraftAction(): void
    {
        $request = new Request([], [Router::ACTION_PARAMETER => DraftHelper::SAVE_AS_DRAFT_ACTION]);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $helper = new DraftHelper($requestStack);
        $this->assertTrue($helper->isSaveAsDraftAction());
    }

    public function testIsAnyAction(): void
    {
        $request = new Request([], [Router::ACTION_PARAMETER => 'any_action']);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $helper = new DraftHelper($requestStack);
        $this->assertFalse($helper->isSaveAsDraftAction());
    }
}
