define(function(require) {
    'use strict';
    function DialogManager() {
        this.dialogs = [];
    }
    DialogManager.prototype = {
        dialogs: null,
        add: function(dialog) {
            this.dialogs.push(dialog);
        },
        remove: function(dialog) {
            var index = this.dialogs.indexOf(dialog);
            if (index === -1) {
                throw new Error('Could not remove unexisting dialog');
            }
            this.dialogs.splice(index, 1);
        },
        updateIncrementalPosition: function(dialogWidget) {
            dialogWidget.setPosition(this.preparePosition(0, 0));
            var offsetLeft = 0;
            var offsetTop = 0;
            var positions = [];
            for (var i = 0; i < this.dialogs.length; i++) {
                var currentDialogWidget = this.dialogs[i];
                if (currentDialogWidget.widget && currentDialogWidget !== dialogWidget) {
                    var dialogEl = currentDialogWidget.widget[0];
                    positions.push({
                        dialog: currentDialogWidget,
                        rect: dialogEl.getBoundingClientRect()
                    });
                }
            }
            var baseRect;
            var basePosition;
            function updateBasePosition() {
                baseRect = dialogWidget.widget[0].getBoundingClientRect();
                basePosition = {
                    top: baseRect.top,
                    left: baseRect.left,
                    width: baseRect.width,
                    height: baseRect.height
                };
            }
            updateBasePosition();
            var exit = false;
            var totalIterations = positions.length;
            while (exit !== true && totalIterations > 0) {
                for (i = 0; i < positions.length; i++) {
                    var position = positions[i];
                    if (this.getRectSimilarity(basePosition, position.rect) < 34) {
                        offsetLeft += 36;
                        offsetTop += 36;
                        dialogWidget.setPosition(this.preparePosition(offsetLeft, offsetTop));
                        updateBasePosition();
                        break;
                    }
                }
                totalIterations--;
            }
        },

        preparePosition: function(offsetLeft, offsetTop) {
            return {
                my: 'center',
                at: 'center+' + offsetLeft + ' center+' + offsetTop,
                of: '#container',
                collision: 'fit'
            };
        },

        getRectSimilarity: function(aRect, bRect) {
            return Math.abs(aRect.top - bRect.top) +
                Math.abs(aRect.left - bRect.left) +
                Math.abs(aRect.top - bRect.top + aRect.height - bRect.height) +
                Math.abs(aRect.left - bRect.left + aRect.width - bRect.width);
        }
    };
    return DialogManager;
});
