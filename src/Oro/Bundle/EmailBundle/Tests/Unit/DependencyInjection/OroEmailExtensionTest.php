<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EmailBundle\DependencyInjection\OroEmailExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroEmailExtensionTest extends ExtensionTestCase
{
    /** @var OroEmailExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroEmailExtension();
    }

    public function testGetAlias(): void
    {
        $this->assertEquals('oro_email', $this->extension->getAlias());
    }

    public function testLoad(): void
    {
        $this->loadExtension($this->extension);

        $this->assertPublicServices([
            'oro_email.autocomplete.mailbox_user_search_handler',
            'oro_email.cache',
            'oro_email.datagrid.origin_folder.provider',
            'oro_email.direct_mailer',
            'oro_email.email.address.helper',
            'oro_email.email.cache.manager',
            'oro_email.email.manager',
            'oro_email.email.model.builder',
            'oro_email.email.thread.provider',
            'oro_email.email_address.entity_manager',
            'oro_email.email_body_type_transformer',
            'oro_email.email_importance_transformer',
            'oro_email.email_recipients.provider',
            'oro_email.email_renderer',
            'oro_email.email_synchronization_manager',
            'oro_email.emailfolder.datagrid_view_list',
            'oro_email.emailseen.datagrid_view_list',
            'oro_email.emailtemplate.datagrid_helper',
            'oro_email.emailtemplate.datagrid_view_list',
            'oro_email.emailtemplate.variable_provider',
            'oro_email.form.configurator.email_configuration',
            'oro_email.form.email',
            'oro_email.form.email.api',
            'oro_email.form.emailtemplate',
            'oro_email.form.handler.email',
            'oro_email.form.handler.email.api',
            'oro_email.form.handler.email_configuration',
            'oro_email.form.handler.emailtemplate',
            'oro_email.form.handler.mailbox',
            'oro_email.form.handler.user_email_config',
            'oro_email.helper.datagrid.emails',
            'oro_email.mailbox.manager',
            'oro_email.mailbox.manager.api',
            'oro_email.mailbox_choice_list',
            'oro_email.mailer.checker.smtp_settings',
            'oro_email.manager.autoresponserule.api',
            'oro_email.manager.email.api',
            'oro_email.manager.email_activity.api',
            'oro_email.manager.email_activity_entity.api',
            'oro_email.manager.email_activity_search.api',
            'oro_email.manager.email_activity_suggestion.api',
            'oro_email.manager.email_attachment_manager',
            'oro_email.manager.email_origin.api',
            'oro_email.manager.emailtemplate.api',
            'oro_email.manager.notification',
            'oro_email.manager.template_email',
            'oro_email.mass_action.mark.read',
            'oro_email.mass_action.mark.unread',
            'oro_email.mass_action.mark_handler',
            'oro_email.migration.demo_data_fixtures_listener.email_associations',
            'oro_email.placeholder.send_email.filter',
            'oro_email.provider.email_recipients.helper',
            'oro_email.provider.email_template_content_provider',
            'oro_email.provider.smtp_settings',
            'oro_email.swiftmailer.mailer.abstract',
            'Oro\Bundle\EmailBundle\Manager\EmailNotificationManager',
        ]);
    }
}
