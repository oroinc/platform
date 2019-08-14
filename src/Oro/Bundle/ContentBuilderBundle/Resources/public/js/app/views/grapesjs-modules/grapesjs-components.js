define(function(require) {
    'use strict';

    var _ = require('underscore');
    var TableComponents = require('./components/table');
    var selectTemplate = require('tpl!orocontentbuilder/templates/grapesjs-select-action.html');

    var ComponentManager = function(options) {
        _.extend(this, _.pick(options, ['builder']));

        this.init();
    };

    ComponentManager.prototype = {
        BlockManager: null,

        Commands: null,

        DomComponents: null,

        init: function() {
            _.extend(this, _.pick(this.builder, ['BlockManager', 'Commands', 'DomComponents', 'RichTextEditor']));

            this.addComponents();

            this.BlockManager.add('table-block', {
                id: 'table',
                label: 'Table',
                category: 'Basic',
                attributes: {
                    class: 'fa fa-table'
                },
                content: {
                    type: 'table'
                }
            });

            this.RichTextEditor.add('formatBlock', {
                icon: selectTemplate({
                    options: {
                        normal: 'Normal text',
                        h1: 'Heading 1',
                        h2: 'Heading 2',
                        h3: 'Heading 3',
                        h4: 'Heading 4',
                        h5: 'Heading 5',
                        h6: 'Heading 6'
                    },
                    name: 'tag'
                }),
                event: 'change',

                attributes: {
                    title: 'Text format',
                    class: 'gjs-rte-action text-format-action'
                },

                priority: 0,

                result: function result(rte, action) {
                    var value = action.btn.querySelector('[name="tag"]').value;

                    if (value === 'normal') {
                        var parentNode = rte.selection().getRangeAt(0).startContainer.parentNode;
                        var text = parentNode.innerText;
                        parentNode.remove();

                        return rte.insertHTML(text);
                    }
                    return rte.exec('formatBlock', value);
                },

                update: function(rte, action) {
                    var value = rte.doc.queryCommandValue(action.name);

                    if (value !== 'false') { // value is a string
                        action.btn.firstChild.value = value;
                    }
                }
            });

            this.sortActionsRte();
        },

        addComponents: function() {
            new TableComponents(this.builder);
        },

        sortActionsRte: function() {
            this.RichTextEditor.actions = _.sortBy(this.RichTextEditor.actions, 'priority');


        }
    };

    return ComponentManager;
});
