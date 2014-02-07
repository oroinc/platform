define(['underscore', 'oro/translator', 'oro/datagrid/mass-action', 'oro/messenger'],
    function(_, __, MassAction, messenger) {
        'use strict';
        var beda = function(){
            console.log('beda');
        };
        var action = MassAction.extend({
            initialize: function(options) {
                var max_length = this.max_element_count;
                MassAction.prototype.initialize.apply(this, arguments);
                this.on('preExecute', function(event, options){
                    var selectionState = this.datagrid.getSelectionState();
                    var isInset = selectionState.inset;
                    var length = Object.keys(selectionState.selectedModels).length;

                    if(!isInset){
                        var totalRecords = this.datagrid.collection.state.totalRecords;

                        length = totalRecords - length;
                    }

                    if(length > max_length){
                        options['doExecute'] = false;
                        messenger.notificationFlashMessage('error', 'Too many records selected. Maximum '+max_length+' records.');
                    }
                    if(length<2){
                        options['doExecute'] = false;
                        messenger.notificationFlashMessage('error', 'Select two or more records.');
                    }

                }, this);
            }
        });
        return action;
    });