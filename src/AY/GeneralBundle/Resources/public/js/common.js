function undef(o){
	return typeof o === 'undefined';
}

//PredefinedText Object 
(function(){
	//constructor
	var PredefinedText = function(selector){
		if(undef(selector)){
			throw new Error('No selector for PredefinedText control');
		};
		
		$(document.body).on('focus', selector, function(e){
			var el = $(e.target);
			if(!el.predefinedText){
				el.predefinedText = el.attr('data-predefined-text');
			}
			
			var t = el.predefinedText;
			if(el.val() === t){
				el.val('');
			}
			el.removeClass('predefined-text');									
		});
		
		$(document.body).on('blur', selector, function(e){
			var el = $(e.target);
			if(!el.predefinedText){
				el.predefinedText = el.attr('data-predefined-text');
			}
			
			var t = el.predefinedText;

			if(el.val().length == 0){
				el.val(t);
				el.addClass('predefined-text');
			}
		});
	}
	if(!undef($)){
		$.predefine = PredefinedText;
	} else {
		window.$ = {};
		window.$.predefine = PredefinedText;
	}
	
})()
