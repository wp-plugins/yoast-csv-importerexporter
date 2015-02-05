jQuery(document).ready(function($){	
	
	var app = {
		init: function(){
			app.toolTip();
			app.upload_script();
			app.start_import();
			app.start_export();
		},

		toolTip: function(){
			/** START: Add tooltip */
			var targets = $( '[rel~=tooltip]' ),
		        target  = false,
		        tooltip = false,
		        title   = false;
		 
		    targets.bind( 'mouseenter', function()
		    {
		        target  = $( this );
		        tip     = target.attr( 'title' );
		        tooltip = $( '<div id="tooltip"></div>' );
		 
		        if( !tip || tip == '' )
		            return false;
		 
		        target.removeAttr( 'title' );
		        tooltip.css( 'opacity', 0 )
		               .html( tip )
		               .appendTo( 'body' );
		 
		        var init_tooltip = function()
		        {
		            if( $( window ).width() < tooltip.outerWidth() * 1.5 )
		                tooltip.css( 'max-width', $( window ).width() / 2 );
		            else
		                tooltip.css( 'max-width', 340 );
		 
		            var pos_left = target.offset().left + ( target.outerWidth() / 2 ) - ( tooltip.outerWidth() / 2 ),
		                pos_top  = target.offset().top - tooltip.outerHeight() - 20;
		 
		            if( pos_left < 0 )
		            {
		                pos_left = target.offset().left + target.outerWidth() / 2 - 20;
		                tooltip.addClass( 'left' );
		            }
		            else
		                tooltip.removeClass( 'left' );
		 
		            if( pos_left + tooltip.outerWidth() > $( window ).width() )
		            {
		                pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
		                tooltip.addClass( 'right' );
		            }
		            else
		                tooltip.removeClass( 'right' );
		 
		            if( pos_top < 0 )
		            {
		                var pos_top  = target.offset().top + target.outerHeight();
		                tooltip.addClass( 'top' );
		            }
		            else
		                tooltip.removeClass( 'top' );
		 
		            tooltip.css( { left: pos_left, top: pos_top } )
		                   .animate( { top: '+=10', opacity: 1 }, 50 );
		        };
		 
		        init_tooltip();
		        $( window ).resize( init_tooltip );
		 
		        var remove_tooltip = function()
		        {
		            tooltip.animate( { top: '-=10', opacity: 0 }, 50, function()
		            {
		                $( this ).remove();
		            });
		 
		            target.attr( 'title', tip );
		        };
		 
		        target.bind( 'mouseleave', remove_tooltip );
		        tooltip.bind( 'click', remove_tooltip );
		    });
			/** END: Add tooltip */
		},

		upload_script: function(){

			$('#fl-yoast-csv').die('click').live('change', function(){
	           //$("#preview").html('');
	    
				$("#imageform").ajaxForm({target: '#csv_upload_result', 
				    
				    beforeSubmit:function(){ 
						$("#drag-drop-area").addClass('upload-ongoing');
					 	$("#csvloadbutton").hide();
					}, 
					
					success:function(){
						console.log('z');
					 	$("#drag-drop-area").removeClass('upload-ongoing');
					 	$("#plupload-upload-ui").slideToggle('slow');
					 	$('#start-import').show();
					 	app.start_draggable();
					 	app.toolTip();
					}, 

					error:function(){ 
						console.log('d');
						$("#drag-drop-area").removeClass('upload-ongoing');
					 	$("#imageloadstatus").hide();
						$("#csvloadbutton").show();
						$('#start-import').hide();
					} 

				}).submit();
					

			});
		},

		start_import: function(){

			$('body').on('click', '#start-import', function(){

				if( !$(this).hasClass('import-complete') ){

					if( $('#csv-column-id').val() ){

						var that = $(this);

						that.val('Working...');
						$.ajax({
							type: 'POST',
							url: _ajax.ajaxurl,
							data: {
								'csv_file': that.attr('data-file'),
								'records': $('#csv_upload_result .updated p strong').text(),
								'id_alias': $('#csv-column-id').val(),
								'title_alias': $('#csv-column-title').val(),
								'description_alias': $('#csv-column-description').val(),
								'keywords_alias': $('#csv-column-keywords').val(),
								'action':'fl_csv_import'
							},
							success: function(msg) {
								$('#csv_upload_result .updated p').html('All done! <strong>' + $('#csv_upload_result .updated p strong').text() + '</strong> entries has been imported! (<a href="#" id="see-results-link">See results</a>)');
								$('.csv-tree-wrap').slideToggle('slow');
								/*$('#csv_upload_result .autohide').slideDown();*/
								that.val('Done');
								that.addClass('import-complete');
								

								var obj = $.parseJSON( msg );
									logs_html = '';

								$.each(obj.logs, function(i, data) {
									/*alert( obj.import_status );*/
									logs_html += '<a href="' + data.permalink + '">' + data.permalink + '</a> (Success)<br />';
								});	

								/*$.each(obj, function(i, res) {
									alert( res.import_status );
									$.each(res.logs, function(j, d) {
										alert( d.title );
									});
								});*/

								$('.csv-tree-wrap').before('<div class="import-results">' + logs_html + '</div>');
								app.after_import();
							}
						});

					}
					else{
						alert( 'Post/Page ID is required. Please try again.' );
					}
				}
				else{
					location.reload();
				}

				return false;

			});
		},

		after_import: function(){

			$('body').on('click', 'a#see-results-link', function(){
				$('div.import-results').slideToggle('slow');
			});
		},

		start_export: function(){

			$('body').on('click', 'input#start-export', function(){
				
				var that = $(this);
					data = $('form#yoast-csv-export').serialize();

				that.val('Please wait...');

				$.ajax({
					type: 'GET',
					url: _ajax.ajaxurl,
					data: data,
					success: function(msg) {

						that.val('Export to CSV');

						if( msg == 'Failed' ){
							alert( 'Invalid request. Please try again.' );
						}
						else{
							document.body.innerHTML += "<iframe src='" + msg + "' style='display: none;' ></iframe>";
						}
					}
				});

				return false;
			});

		},

		start_draggable: function(){

			/*$('#csv_xml_form').mouseover(function(){
				setTimeout(function(){
					$('#csv_upload_result .autohide').slideUp();
				}, 5000);
			});*/

			$( ".fl_yoast_csv_settings_page .draggable" ).draggable( { revert: true } );
		    $( ".fl_yoast_csv_settings_page .droppable" ).droppable({
		      	drop: function( event, ui ) {

		      		console.log( $(ui.draggable).text() );

		      		$( this )
		          		.addClass( "ui-state-highlight" )
		          		.val( $(ui.draggable).text() );
		      	}
		    });
		}
	};

	app.init();
	

});