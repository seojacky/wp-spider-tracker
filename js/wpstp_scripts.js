jQuery(document).ready(function($) {		
					//toggle single
					$(".wpstp_header").click(function(){
						$(this).next(".wpstp_subTable").slideToggle('. $slideDelay .')
						return false;
					});			
					//collapse all
					$(".collapse_all").click(function(){
						$(".wpstp_subTable").slideUp('. $slideDelay .')
						return false;
					});		
					//expand all
					$(".expand_all").click(function(){
						$(".wpstp_subTable").slideDown('. $slideDelay .')
						return false;
					});		
				});	