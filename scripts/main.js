console.log('loading correctly');
jQuery(document).ready(function($) {
	updateLWDConditions();

	$('.lwd-form :input').change(function(event) {
		updateLWDConditions();
	});

	function updateLWDConditions( )
	{
		$('.lwd-form').each(function(index, el) {
			// FILTER OUT ALL CONDITIONAL ELEMENTS
			var formid = $(this).attr('id').substring(9);
			
			var ele = $(this).find('*').filter(function(){
				var attrs = this.attributes;
				for (var i=0; i<attrs.length; i++) {
					if (attrs[i].name.indexOf("data-condition-")==0) return true;
				}
				return false;
			});

			console.log(ele);

			ele.each(function(index, el) {
				// TEST ELEMENT AGAINST CONDITION
				var attrs = el.attributes;
				console.log(attrs);
				var passed = 0;
				var rules = 0;

				var condition = $(el).attr('data-condition');

				if( !condition ) condition = 'AND';

				for (var i=0; i<attrs.length; i++) {
					if (attrs[i].name.indexOf("data-condition-")!=0) continue;
					else rules ++;

					var attrname = attrs[i].name.substring(15);

					var index = attrname.indexOf("-")

					if (index != -1)
					{
						attrname = attrname.substring(index+1, attrname.length);
					}
					console.log(attrname);
					
					var target = '#lwd-element-'+attrname;
					var value = attrs[i].value;

					console.log('current element is '+el.id);


					if( $(target).find('.lwd-radio-wrap').length > 0 || $(target).find('.lwd-checkbox-wrap').length > 0 )
					{
						// IF TARGET ISNT CHECKED, LEAVE IT AS FAILED
						if( $(target).find(':input[value='+value+']:checked').length == 0 )
						{
							console.log('failed on box rule of '+target+' '+value );
							continue;
						}
					}
					else if( $(target).hasClass('lwd-text-wrap') )
					{
						// IF TARGET DOESN'T EQUATE TO VALUE, STILL FAIL
						if( $(target).find(':input').val() != value )
						{
							console.log('failed on text rule of '+target+' '+value );
							continue;
						}
					}
					console.log('allow on rule of '+target+' '+value );
					passed ++;
				}

				// IF IT DOESNT PASS ALL THE RULES, HIDE
				if( passed < rules && 'AND' == condition )
				{
					console.log('failed on AND so hide '+el.id+' passed = '+passed);
					$(el).hide();
				} // IF IT DOESN'T PASS A SINGLE RULE
				else if( passed < 1 && 'OR' == condition )
				{
					console.log('failed on OR so hide '+el.id+' passed = '+passed);
					$(el).hide();
				}
				else
				{
					console.log('passed so show '+el.id+' passed = '+passed);
					$(el).show();
				}
			});

		});
	}
});