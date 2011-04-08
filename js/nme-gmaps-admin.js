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
});
