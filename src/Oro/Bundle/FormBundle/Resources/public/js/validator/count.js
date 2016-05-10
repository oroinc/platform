define(['jquery', 'underscore', './number'], function($, _, numberValidator) {
    'use strict';

    var defaultParam = {
        exactMessage: 'This collection should contain exactly {{ limit }} element.|' +
            'This collection should contain exactly {{ limit }} elements.',
        maxMessage: 'This collection should contain {{ limit }} element or less.|' +
            'This collection should contain {{ limit }} elements or less.',
        minMessage: 'This collection should contain {{ limit }} element or more.|' +
            'This collection should contain {{ limit }} elements or more.'
    };

    /**
     * Return only checked, unchecked choice fields doesn't send to server, and for server this collection is empty
     *
     * @param {jQuery} $fields
     * @returns {Integer}
     */
    function getCheckboxCount($fields) {
        return $fields.filter(':checked').length;
    }

    /**
     * Replace collection child key in child name with '[]',
     * Example:
     *      form[additional][field1] > form[additional][]
     *      form[additional][][field1] > form[additional][][field1]
     *      form[additional][1][field1] > form[additional][][field1]
     *
     * @param {String} collectionName
     * @param {String} name
     * @returns {String}
     */
    function getChildName(collectionName, name) {
        return collectionName + name.replace(collectionName, '').replace(/\[[^\]]*/, '[');
    }

    /**
     * All fields with name 'collectionName*'
     * Example: 'form[additional][field1]'
     *
     * @param {String} collectionName
     * @param {$.validator} validator
     * @returns {jQuery}
     */
    function findByCollectionName(collectionName, validator) {
        return $(validator.currentForm).find('[name^="' + collectionName + '"]');
    }

    /**
     * return only fields, with name matched to fierst element childName
     *
     * @param {String} collectionName
     * @param {jQuery} $fields
     * @returns {jQuery}
     */
    function filterChildFields(collectionName, $fields) {
        var childName = getChildName(collectionName, $fields.get(0).name);
        return $fields.filter(function() {
            return getChildName(collectionName, this.name) === childName;
        });
    }

    function getCount(validator, element) {
        //Example: collectionName = 'form[additional]'
        var collectionName = $(element).data('collectionName');
        if (!collectionName) {
            //use old logic if data-collection-name not found
            return getCheckboxCount(validator.findByName(element.name));
        }

        var $fields = findByCollectionName(collectionName, validator);
        if ($fields.length === 0) {
            return $fields.length;
        }

        //if all $fields is a checkbox/radio fields
        var $choicesFields = $fields.filter(':checkbox, :radio');
        if ($choicesFields.length === $fields.length) {
            return getCheckboxCount($choicesFields);
        }

        return filterChildFields(collectionName, $fields).length;
    }

    /**
     * @export oroform/js/validator/count
     */
    return [
        'Count',
        function(value, element, param) {
            value = getCount(this, element);
            return numberValidator[1].call(this, value, element, param);
        },
        function(param, element) {
            var value = getCount(this, element);
            var placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.count = value;
            return numberValidator[2].call(this, param, element, value, placeholders);
        }
    ];
});
