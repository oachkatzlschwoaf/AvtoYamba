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
			
			if(!params.submitable){
				return false;
			}
		}
		
		
		$(this.input.code).keyup(handleInput);
		$(this.input.code).keyup(handleInput);
		
		if(submit){
			$(submit).click(handleSubmit);
		}			
	}
	
	c.prototype = {
		submit: function(e){
			e.stopPropagation();
			
			var url = this.container.find('form').attr('action') + '/$number';
			
			var number = this.input.code.val() + this.input.region.val();
			
			url = url.replace('$number', number);
			
			$.ajax({
				url: url,
				type: "post"
			});
			
			return false;
		},
		
		validator: {
			code: function( str, char ){
				return false;
				
				var sl = str.length,
					_rc = 'АВЕКМНОРСТУХ',
					_r = '',
					char = char || null;
					
				function validateChar(c){
					if(sl > 1 && sl < 4){
						_r = '\d';
					}
					
					if(sl > 4 || sl == 1){
						_r = '[' + _rc + ']';
					}
					
					return new RegExp( _r ).test( с ) || false;;
				}
				
				if(char){
					return validateChar(char);
				}
				
			},
			
			region: function( s ){
				return false;
				
				var _r = '\\d';

				if( s.length > 1 )
					_r += '{' + s.length + '}';

				return new RegExp( _r ).test( s ) || false;
			}
		},
		
		validate: function( e ){
			return false;
			
			var t = $(e.target),
				type = t.attr('data-type');
			
			var valid = this.validator[type](t.val(), String.fromCharCode(e.keyCode));
			if(!valid){
				return false;
			}
		}
	};
	
	window.CarNumber = c;
})();


