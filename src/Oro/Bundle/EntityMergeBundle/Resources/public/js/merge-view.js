var mergeView = {

    entitySelectAllHandler: function(){
        var entityId = $(this).parents('td').find('input[type="radio"]').val();
        $('.entity-merge-blocks-wrapper').find('input[value="'+entityId+'"]').click();
    },
    entityValueSelectHandler: function(){
        $(this).parents('tr').find('.merge-entity-representative').addClass('entity-merge-not-selected');
        $(this).parents('td').find('.merge-entity-representative').removeClass('entity-merge-not-selected');
    },
    resetViewState:function(){
        $('input[type="radio"]:checked').click();
    },
    init:function(){
        $('.entity-merge-select-all').click(mergeView.entitySelectAllHandler);
        $('.entity-merge-blocks-wrapper').find('input[type="radio"]').click(mergeView.entityValueSelectHandler);
        mergeView.resetViewState();
    }
};