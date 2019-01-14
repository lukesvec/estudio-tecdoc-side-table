<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.min.css" rel="stylesheet"/>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2_locale_cs.min.js"></script>
		<script>
			// TECDOC ADVANCE SELECT

		function formatResultMultiBrand(data) {
			
		  var classAttr = $(data.element).attr('class');
		  var hasClass = typeof classAttr != 'undefined';
		  classAttr = hasClass ? ' ' + classAttr : '';
		
		  var $result = $(
		    '<div class="row">' +
		    '<div style="width:100%;display:inline-block;float:left;padding-right:10px;line-height:12px;" class="' + classAttr + '">' + data.text + '</div>' +
		    '</div>'
		  );
		  
		  return $result;
		}
		
		
		function formatResultMultiModel(data) {
			
		  var date1 = $(data.element).data('date');
		  var classAttr = $(data.element).attr('class');
		  
		  var hasClass = typeof classAttr != 'undefined';
		  classAttr = hasClass ? ' ' + classAttr : '';
		
		  var $result = $(
		    '<div class="row">' +
		    '<div style="width:250px;display:inline-block;float:left;padding-right:10px;line-height:12px;" class="' + classAttr + '">' + data.text + '</div>' +
		    '<div style="width:100px;display:inline-block;float:left;padding-right:10px;line-height:12px;" class="' + classAttr + '">' + date1 + '</div>' +
		    '</div>'
		  );
				    
		  return $result;
		}
		
		function formatResultMultiMotor(data) {
			
		  var engine = $(data.element).data('engine');
		  var kw = $(data.element).data('kw');
		  var classAttr = $(data.element).attr('class');
		  
		  var hasClass = typeof classAttr != 'undefined';
		  classAttr = hasClass ? ' ' + classAttr : '';
		
		  var $result = $(
		    '<div class="row">' +
		    '<div style="width:225px;display:inline-block;float:left;padding-right:10px;line-height:12px;" class="' + classAttr + '">' + data.text + '</div>' +
		    '<div style="width:150px;display:inline-block;float:left;padding-right:10px;line-height:12px;" class="' + classAttr + '">' + engine + '</div>' +
		    '<div style="width:50px;display:inline-block;float:left;padding-right:10px;line-height:12px;" class="' + classAttr + '">' + kw + '</div>' +
		    '</div>'
		  );
				    
		  return $result;
		}
		
		
		$(function() {
			$('#sideTecBrand').select2({
				language: "cs",
			    width: '100%',
			    formatResult: formatResultMultiBrand,
			    allowClear: true,
			    placeholder: "Značka",
			    sortResults: function(data) {
			        return data.sort(function (a, b) {
			            a = a.text.toLowerCase();
			            b = b.text.toLowerCase();
			            if (a > b) {
			                return 1;
			            } else if (a < b) {
			                return -1;
			            }
			            return 0;
			        });
			    }
			});
			$('#sideTecModel').select2({
				language: "cs",
			    width: '100%',
			    formatResult: formatResultMultiModel,
			    allowClear: true,
			    dropdownAutoWidth : true,
			    sortResults: function(data) {
			        return data.sort(function (a, b) {
			            a = a.text.toLowerCase();
			            b = b.text.toLowerCase();
			            if (a > b) {
			                return 1;
			            } else if (a < b) {
			                return -1;
			            }
			            return 0;
			        });
			    }
			});
			$('#sideTecMotor').select2({
				language: "cs",
			    width: '100%',
			    formatResult: formatResultMultiMotor,
			    allowClear: true,
			    dropdownAutoWidth : true,
			    sortResults: function(data) {
			        return data.sort(function (a, b) {
			            a = a.text.toLowerCase();
			            b = b.text.toLowerCase();
			            if (a > b) {
			                return 1;
			            } else if (a < b) {
			                return -1;
			            }
			            return 0;
			        });
			    }
			});
		});
  

		</script>
