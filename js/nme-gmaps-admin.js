jQuery(document).ready(function(){
    jQuery('.remove-admin-gmaps').live('click',function(){
        var action = confirm('Do You Really want to perform this action');
        if(action==true){
            jQuery(this).parent().parent().parent().addClass('del-current');
            var id = jQuery(this).attr('rel');
            var data = {
                action:'nme_delete_gmaps',
                id:id
            };
            jQuery.post(ajaxurl,data,function(response){
                if(response=='true'){
                    jQuery('.del-current').css('background-color','#FFF000');
                    jQuery('.del-current').remove();
                }
            });
        }
    });


    jQuery('.nme_location_table').sortable({
        containerSelector: 'table',
        itemPath: '> tbody',
        itemSelector: 'tr',
        handle: 'i.icon-move',
        placeholder: '<tr class="placeholder"/>',
        onDrop: function (item, container, _super) {
            _super(item, container);
            var ids = new Array();
            jQuery('.nme_location_table tbody tr').each(function (){
                var rel = jQuery(this).children().find('.icon-move').attr('rel');
                ids.push(rel);
            });
            if( ids.length ){
                jQuery.ajax({
                    type    :   'POST',
                    url     :   ajaxurl,
                    data    :   {
                        action : 'nme_gmaps_update_order',
                        ids     :   ids
                    },
                    success: function(){}
                });
            }
        }
    });
});
