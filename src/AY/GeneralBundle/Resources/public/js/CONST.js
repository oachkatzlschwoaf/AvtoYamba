(function(){
	var Const = function(){
		
		var c = {
			number: {
				trans: {
					ru: {
						'А': 'A',
						'В': 'B',
						'Е': 'E',
						'К': 'K',
						'М': 'M',
						'Н': 'H',
						'О': 'O',
						'Р': 'P',
						'С': 'C',
						'Т': 'T',
						'У': 'Y',
						'Х': 'X'
					}
				}
			}
		}
		
		this.get = function(name){
			return c[name]; 
		}
	}
	
	window.CONST = new Const;
})();