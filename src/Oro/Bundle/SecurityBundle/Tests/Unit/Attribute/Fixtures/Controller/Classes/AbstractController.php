<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Fixtures\Controller\Classes;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

abstract class AbstractController extends SymfonyAbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Acl(
        id: "user_action_in_abstract_controller",
        type: "entity",
        class: "AcmeBundle\Entity\SomeClass",
        permission: "VIEW",
        groupName: "Test Group",
        label: "Action In Abstract Controller",
    )]
    public function testAction()
    {
        return $this->getResponse();
    }

    abstract protected function getResponse();
}
