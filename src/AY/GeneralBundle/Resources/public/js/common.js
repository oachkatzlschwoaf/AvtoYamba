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
	
})();

$.predefine('.predefined-text-control');

(function(){
	var c = function(container, params){
		var cc = null,
			that = this,
			params = params || {};
		
		this.params = params;
		
		this.container = cc = $(container);
		
		this.input = {
			code: cc.find('[data-type="code"]'),
			region: cc.find('[data-type="region"]')
		};
		
		var submit = cc.find('[type="submit"]');
		
		function handleInput(e){
			that.validate.call(that, e);
		}
		
		function handleSubmit(e){
			e.stopPropagation();
			that.submit.call(that, e);
			
			return false;
		}
		
		
		// $(this.input.code).keyup(handleInput);
		// $(this.input.code).keyup(handleInput);
		
		if(submit){
			$(submit).click(handleSubmit);
		}			
	}
	
	c.prototype = {
		submit: function(e){
			e.stopPropagation();
			
			if(!this.validate()){
				return false;
			}
			
			var url = this.container.find('form').attr('action');
			
			var number = this.input.code.val() + this.input.region.val();
			
			url = url.replace('$number', number);
			
			var success = function(){};
			if(this.params.submitable){
				success = function(){
					document.location = url + '?number=' + number;
				}
			}
			
			$.ajax({
				url: url,
				type: "get",
				data: {
					number: number	
				},
				
				success: success
			});
			
			return false;
		},
		
		validator: {
			code: function( str ){
				str = (str).toString();
				var _rc = 'АВЕКМНОРСТУХ',
					_r = '[' + _rc + ']\\d{3}[' + _rc + ']{2}';
				
				return new RegExp( _r ).test( str.toUpperCase() ) || false;
			},
			
			region: function( s ){
				var _r = '\\d';

				if( s.length > 1 )
					_r += '{' + s.length + '}';

				return new RegExp( _r ).test( s ) || false;
			}
		},
		
		validate: function(){
			var valid = true;
			
			if(!this.validator.code(this.input.code.val())){
				this.input.code.parent().addClass('error');
				valid = false;
			} else {
				this.input.code.parent().removeClass('error');
			}

			if(!this.validator.region(this.input.region.val())){
				this.input.region.parent().addClass('error');
				valid = false;
			} else {
				this.input.region.parent().removeClass('error');
			}
			
			
			return valid;
		}
	};
	
	window.CarNumber = c;
})();


