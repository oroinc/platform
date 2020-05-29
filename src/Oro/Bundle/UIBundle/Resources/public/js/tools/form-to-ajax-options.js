define(['jquery', 'jquery.form'], function($) {
    'use strict';

    /**
     * Utility fn for deep serialization
     *
     * @param data
     * @returns {Array}
     */
    function serialize(data) {
        let part;
        const serialized = $.param(data).split('&');
        const result = [];
        for (let i = 0; i < serialized.length; i += 1) {
            // #252; undo param space replacement
            serialized[i] = serialized[i].replace(/\+/g, ' ');
            part = serialized[i].split('=');
            // #278; use array instead of object storage, favoring array serializations
            result.push([decodeURIComponent(part[0]), decodeURIComponent(part[1])]);
        }
        return result;
    }

    /**
     * XMLHttpRequest Level 2 file uploads
     *
     * @param {Array} arrayData
     * @param {Object} extraData
     * @param {Object} options
     * @returns {Object}
     */
    function fileUploadXhr(arrayData, extraData, options) {
        const formData = new FormData();

        for (let i = 0; i < arrayData.length; i += 1) {
            formData.append(arrayData[i].name, arrayData[i].value);
        }

        if (extraData) {
            const data = serialize(extraData);
            for (let i = 0; i < data.length; i += 1) {
                if (data[i]) {
                    formData.append(data[i][0], data[i][1]);
                }
            }
        }

        const beforeSend = options.beforeSend;
        options = $.extend(true, {}, $.ajaxSettings, options, {
            contentType: false,
            processData: false,
            cache: false,
            data: null,
            beforeSend: function(xhr, options) {
                options.data = formData;
                if (beforeSend) {
                    return beforeSend.call(this, xhr, options);
                }
            }
        });

        return options;
    }

    /**
     * Fetches data from form an prepares options for jQuery.ajax()
     *
     * @param {jQuery} $form of form element
     * @param {Object=} options base options for jQuery.ajax()
     * @returns {Object}
     */
    function formToAjaxOprions($form, options) {
        let extraData;
        let query;
        let extraQuery;

        const action = $form.attr('action');
        const method = $form.attr('method');

        let url = (typeof action === 'string') ? action.trim() : '';
        url = url || window.location.href || '';
        if (url) {
            // clean url (don't include hash vaue)
            url = (url.match(/^([^#]+)/) || [])[1];
        }

        options = $.extend(true, {
            url: url,
            type: method || 'GET'
        }, options || {});

        const arrayData = $form.formToArray();

        if (options.data) {
            extraData = options.data;
            extraQuery = $.param(options.data);
        }

        query = $.param(arrayData);
        if (extraQuery) {
            query = query ? (query + '&' + extraQuery) : extraQuery;
        }
        if (options.type.toUpperCase() === 'GET') {
            options.url += (options.url.indexOf('?') >= 0 ? '&' : '?') + query;
            options.data = null; // data is null for 'get'
        } else {
            options.data = query; // data is the query string for 'post'
        }

        if (
            $form.find('input[type=file]:enabled[value!=""]').length > 0 ||
                $form.attr('enctype') === 'multipart/form-data' ||
                $form.attr('encoding') === 'multipart/form-data'
        ) {
            options = fileUploadXhr(arrayData, extraData, options);
            options.type = method || 'POST';
        }

        return options;
    }

    return formToAjaxOprions;
});

