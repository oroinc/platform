<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Fixtures\Controller\Classes;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

#[Acl(
    id: "user_test_extended_controller",
    type: "action",
    groupName: "Test Group",
    label: "Extended test controller for ACL",
)]
class ExtendedController extends MainTestController
{
    /**
     * @return Response
     */
    public function test2Action()
    {
        return new Response('test');
    }

    /**
     * @return Response
     */
    #[AclAncestor(
        id: "user_test_main_controller_action1",
    )]
    public function test3Action()
    {
        return new Response('test');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Acl(
        id: "user_test_main_controller_action4_rewrite",
        type: "action",
        groupName: "Another Group",
        label: "Action 4 Rewrite",
    )]
    public function test4Action()
    {
        return new Response('test');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Acl(
        id: "user_test_main_controller_action5",
        type: "action",
        groupName: "Another Group",
        label: "Action 5",
    )]
    public function test5Action()
    {
        return new Response('test');
    }
}
