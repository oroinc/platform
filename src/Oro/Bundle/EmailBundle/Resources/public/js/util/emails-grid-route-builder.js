import routing from 'routing';

export default {
    generate(folderId) {
        const url = new URL(routing.generate('oro_email_user_emails'), window.location.origin);

        if (Number(folderId)) {
            url.searchParams.set('grid[user-email-grid]', 'i=1');
            url.searchParams.set('f[folders][value][]', folderId);
        }

        return url.toString();
    }
};
