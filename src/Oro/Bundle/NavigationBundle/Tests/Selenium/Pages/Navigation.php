<?php

namespace Oro\Bundle\NavigationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class Navigation
 *
 * @package Oro\Bundle\NavigationBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\NavigationBundle\Tests\Selenium\Pages\Navigation openNavigation() openNavigation()
 * @method \Oro\Bundle\NavigationBundle\Tests\Selenium\Pages\Navigation assertTitle() assertTitle($title, $message = '')
 */
class Navigation extends AbstractPage
{
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $tabs;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $menu;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element  */
    protected $pinbar;
    /** @var string  */
    protected $xpathMenu = '';

    public function __construct($testCase, $args = array())
    {
        if (array_key_exists('url', $args)) {
            $this->redirectUrl = $args['url'];
        }

        parent::__construct($testCase);

        $this->tabs = $this->test->byId("main-menu");

        $this->pinbar = $this->test->byXpath("//div[contains(@class, 'pin-bar')]");
    }

    public function tab($tab)
    {
        $this->test->moveto(
            $this->tabs->element($this->test->using('xpath')->value("ul/li/a[normalize-space(.) = '{$tab}']"))
        );
        $this->xpathMenu = "//div[@id = 'main-menu']/ul" . "/li[a[normalize-space(.) = '{$tab}']]";
        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function menu($menu)
    {
        $this->test->moveto($this->test->byXpath($this->xpathMenu . "/ul/li/a[normalize-space(.) = '{$menu}']"));
        $this->xpathMenu = $this->xpathMenu . "/ul/li[a[normalize-space(.) = '{$menu}']]";

        return $this;
    }

    public function open()
    {
        $this->test->byXpath($this->xpathMenu . '/a')->click();

        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    public function openMyMenu()
    {
        $this->test->byXPath("//ul[@class='nav pull-right user-menu']//a[@class='dropdown-toggle']")->click();

        return $this;
    }
}
