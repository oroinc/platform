define(function() {
    'use strict';

    const patterns = {
        // Symfony HTML5 mode email validator
        email_html5: /^[a-z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i,
        // Symfony loose mode email validator
        email_loose: /^.+@\S+\.\S+$/
    };

    patterns.email = patterns.email_html5;

    return patterns;
});
