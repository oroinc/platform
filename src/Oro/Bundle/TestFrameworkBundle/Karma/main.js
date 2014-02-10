/*global requirejs*/
/*jshint browser:true*/
(function (files, callback) {
    'use strict';
    var file, match, tests = [], libs = {};

    for (file in files) {
        if (files.hasOwnProperty(file)) {
            // collect tests
            if (/Spec\.js$/.test(file)) {
                tests.push(file);
            }
            // collect test framework's libs
            match = file.match(/TestFrameworkBundle\/Karma\/lib\/(?:[\w\W]+\/)*([\w\-\.]+)\.js$/);
            if (match && match[1] !== 'require-config') {
                libs[match[1]] = file.slice(0, -3);
            }
        }
    }

    requirejs.config({
        // Karma serves files from '/base'
        baseUrl: '/base/web/bundles',

        paths: libs,

        // ask Require.js to load these files (all our tests)
        deps: tests,

        // start test run, once Require.js is done
        callback: callback
    });
}(window.__karma__.files, window.__karma__.start));
