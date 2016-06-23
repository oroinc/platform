<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\UserBundle\Form\Type\EmailSettingsButtonType;

class EmailSettingsButtonTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailSettingsButtonType
     */
    protected $type;

    /** @var Request */
    protected $request;

    protected $form;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->request = new Request();
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
                    ->disableOriginalConstructor()
                    ->getMock();
        $this->type = new EmailSettingsButtonType($this->request);
    }

    public function testBuildViewUserConfig()
    {
        $view = new FormView();
        $this->request->attributes->add(
            [
                '_route' => 'oro_user_config',
                '_route_params' => ['test params']
            ]
        );
        $this->type->buildView($view, $this->form, []);
        $this->assertEquals('oro_user_config_emailsettings', $view->vars['route']);
        $this->assertEquals(['test params'], $view->vars['routeParams']);
    }

    public function testBuildViewUserProfileConfig()
    {
        $view = new FormView();
        $this->request->attributes->add(
            [
                '_route' => 'oro_user_profile_config',
            ]
        );
        $this->type->buildView($view, $this->form, []);
        $this->assertEquals('oro_user_profile_config_emailsettings', $view->vars['route']);
    }

    public function testGetParent()
    {
        $this->assertEquals('hidden', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_user_emailsettings_button', $this->type->getName());
    }
}
