<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

abstract class AbstractController extends SymfonyAbstractController
{
    /**
     * @Acl(
     *      id = "user_action_in_abstract_controller",
     *      type="entity",
     *      class="AcmeBundle\Entity\SomeClass",
     *      permission="VIEW",
     *      group_name="Test Group",
     *      label="Action In Abstract Controller"
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testAction()
    {
        return $this->getResponse();
    }

    abstract protected function getResponse();
}
