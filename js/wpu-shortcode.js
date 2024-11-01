(function() {
    tinymce.create('tinymce.plugins.wpu_shortcode', {
        init : function(ed, url) {
            url = url.replace("wpu-shortlinks/js","wpu-shortlinks/");

            ed.addButton('wpu_shortcode', {
                title : wpu_shortlinks_ast,
                image : url +'/images/wpu_shortlink.png',
                onclick : function() {
					var shortlink_text;
                    var shortlink_id;
					shortlink_text = ed.selection.getContent();
					if(shortlink_text=="")
						shortlink_text = prompt(wpu_shortlinks_slt,wpu_shortlinks_sltd);
                    
                    shortlink_id = prompt(wpu_shortlinks_slidt,"");
					if (shortlink_text!=null && shortlink_text!=""){
				        if (shortlink_id!=null && shortlink_id!="")
                            strsc = '[wpu id="'+shortlink_id+'"]';
                        else
                            strsc = '[wpu]';
                        
						ed.selection.setContent(strsc + shortlink_text + '[/wpu]');
					}
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('wpu_shortcode', tinymce.plugins.wpu_shortcode);
})();