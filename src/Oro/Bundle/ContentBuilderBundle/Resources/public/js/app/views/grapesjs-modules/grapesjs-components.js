define(function(require) {
    'use strict';

    var _ = require('underscore');
    var TableComponents = require('orocontentbuilder/js/app/views/grapesjs-modules/components/table');
    var TableResponsiveComponent = require('orocontentbuilder/js/app/views/grapesjs-modules/components/table-responsive');
    var selectTemplate = require('tpl!orocontentbuilder/templates/grapesjs-select-action.html');

    /**
     * Create component manager
     * @param options
     * @constructor
     */
    var ComponentManager = function(options) {
        _.extend(this, _.pick(options, ['builder']));

        this.init();
    };

    /**
     * Component manager methods
     * @type {{
     *  BlockManager: null,
     *  Commands: null,
     *  DomComponents: null,
     *  init: init,
     *  addComponents: addComponents,
     *  sortActionsRte: sortActionsRte
     *  }}
     */
    ComponentManager.prototype = {
        /**
         * @property {Object}
         */
        BlockManager: null,

        /**
         * @property {Object}
         */
        Commands: null,

        /**
         * @property {Object}
         */
        DomComponents: null,

        /**
         * @property {Object}
         */
        RichTextEditor: null,

        /**
         * Create manager
         */
        init: function() {
            _.extend(this, _.pick(this.builder, ['BlockManager', 'Commands', 'DomComponents', 'RichTextEditor']));

            this.addComponents();
            this.addBlocks();
            this.addActionRte();
        },

        /**
         * Add new component block
         */
        addBlocks: function() {
            this.BlockManager.add('responsive-table', {
                id: 'table-responsive',
                label: 'Table',
                category: 'Basic',
                attributes: {
                    class: 'fa fa-table'
                },
                content: {
                    type: 'table-responsive'
                }
            });
        },

        /**
         * Add Rich Text Editor action
         */
        addActionRte: function() {
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
        },

        /**
         * Add components
         */
        addComponents: function() {
            new TableComponents(this.builder);
            new TableResponsiveComponent(this.builder);
        }
    };

    return ComponentManager;
});
