<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page\Element;

use Behat\Gherkin\Node\TableNode;
use SensioLabs\Behat\PageObjectExtension\PageObject\Element;
use SensioLabs\Behat\PageObjectExtension\PageObject\Page;

class LoginForm extends Element
{
    /**
     * @var array|string $selector
     */
    protected $selector = 'form#login-form';

    protected $fieldsMap = [
        'Username' => '_username',
        'Password' => '_password'
    ];

    /**
     * @param TableNode $table
     *
     * @return Page
     */
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->fillField($this->fieldsMap[$row[0]], $row[1]);
        }
    }
}
