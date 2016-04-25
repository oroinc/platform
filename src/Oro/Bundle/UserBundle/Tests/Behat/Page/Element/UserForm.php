<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page\Element;

use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Page\Element\BaseForm;

class UserForm extends BaseForm
{
    /**
     * @var array|string $selector
     */
    protected $selector = 'div#container form';

    protected $fieldsMap = [
        'username' => 'oro_user_user_form[username]',
        'first name' => 'oro_user_user_form[firstName]',
        'last name' => 'oro_user_user_form[lastName]',
        'password' => 'oro_user_user_form[plainPassword][first]',
        're-enter password' => 'oro_user_user_form[plainPassword][second]',
        'email' => 'oro_user_user_form[email]',
    ];
}
