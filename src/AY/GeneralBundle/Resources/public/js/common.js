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
		
		var PT = this;		
		var elems = $(selector),
				t = null,
				cls = CONST.CLS.PREDEFINED_TEXT;
		
		
		
		this.show = function(placeholder, input){
			if(!$(placeholder).parent().hasClass("error") && input.val() === ""){
				$(placeholder).show();
			}
		}
		
		this.hide = function(placeholder, input){
			$(placeholder).hide();
		}
		
		elems.each(function(el){
			el = $(elems[el]);
			el.val("");
			PT.show(el.parent().find('.placeholder')[0], el);
		});
		
		
		$(document.body).on('focus', selector, function(e){
			PT.hide($(e.target).parent().find('.placeholder'), $(e.target));							
		});
		
		$(document.body).on('blur', selector, function(e){
			PT.show($(e.target).parent().find('.placeholder'), $(e.target))
		});
		
		
	}
	
	if(!undef($)){
		$.predefine = PredefinedText;
	} else {
		window.$ = {};
		window.$.predefine = PredefinedText;
	}
	
})();

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
			
			if(!this.container.find("form").valid()){
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
			var valid = true,
					cls = CONST.CLS.PREDEFINED_TEXT;
			
			if(!this.validator.code(this.input.code.val()) || this.input.code.hasClass(cls)){
				this.input.code.parent().addClass('error');
				valid = false;
			} else {
				this.input.code.parent().removeClass('error');
			}

			if(!this.validator.region(this.input.region.val()) || this.input.region.hasClass(cls)){
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

$(function(){
	$.validator.addMethod("carnumber", function(s){
			var str = (s).toString();
			var _rc = 'АВЕКМНОРСТУХ',
				_r = '[' + _rc + ']\\d{3}[' + _rc + ']{2}';
	
			return new RegExp( _r ).test( str.toUpperCase() ) || false;
		}, "");
	
	$.validator.addMethod("carregion", function(s){
			var _r = '\\d';

			if( s.length > 1 )
				_r += '{' + s.length + '}';

			return new RegExp( _r ).test( s ) || false;
		}, "");
})

$(function(){
	$('[data-validation]').each(function(index, form){
		$(form).validate({
			rules: {
				
			},
			errorClass: "error",
			highlight: function(element, errorClass) {
				$(element).parent().addClass(errorClass);
			},
			unhighlight: function(element, errorClass) {
				$(element).parent().removeClass(errorClass);
			},
			
			errorPlacement: function(error, element){
				if(element.attr("name") !== 'carnumber' && element.attr("name") !== 'carregion'){
					error.insertAfter(element);
				}
			},
			errorElement: "em",
			messages: {
				carnumber: {
					required: false
				}, 
				carregion: {
					required: false
				}
			}
		});
	})
});


