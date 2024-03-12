<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Fixtures\Controller\Classes;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

#[Acl(
    id: "user_test_main_controller",
    type: "action",
    groupName: "Test Group",
    label: "Test controller for ACL",
)]
class MainTestController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Acl(
        id: "user_test_main_controller_action1",
        type: "entity",
        class: "AcmeBundle\Entity\SomeClass",
        permission: "VIEW",
        groupName: "Test Group",
        label: "Action 1",
    )]
    public function test1Action()
    {
        return new Response('test');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Acl(
        id: "user_test_main_controller_action2",
        type: "action",
        groupName: "Another Group",
        label: "Action 2",
    )]
    public function test2Action()
    {
        return new Response('test');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[AclAncestor(
        id: "user_test_main_controller_action2",
    )]
    public function test3Action()
    {
        return new Response('test');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Acl(
        id: "user_test_main_controller_action4",
        type: "action",
        groupName: "Another Group",
        label: "Action 4",
    )]
    public function test4Action()
    {
        return new Response('test');
    }

    public function testNoAclAction()
    {
        return new Response('test');
    }

    public function noActionMethod()
    {
        return array();
    }
}
