/*global define*/
/*jslint nomen: true*/
define([
    'orosegment/js/segment-editor'
], function (SegmentEditor) {
    'use strict';

    return function (options) {
        var segmentEditor;

        segmentEditor = new SegmentEditor(options);
        options._sourceElement.remove();

        return segmentEditor;
    };
});
