<?php
namespace Oro\Bundle\EmailBundle\Async;

class Topics
{
    const SEND_AUTO_RESPONSE = 'oro.email.send_auto_response';
    const ADD_ASSOCIATION_TO_EMAILS = 'oro.email.add_association_to_emails';
    const UPDATE_EMAIL_OWNER_ASSOCIATIONS = 'oro.email.update_email_owner_associations';
    const SYNC_EMAIL_SEEN_FLAG = 'oro.email.sync_email_seen_flag';
    const PURGE_EMAIL_ATTACHMENT = 'oro.email.purge_email_attachment';
}
