/* Copyright (c) 2014 Alexandru Boboc
 * Droptabs v.1.1 Jquery Plugin
 * Tested with JQuery 1.11.1
 */

(function($) {

    $.fn.droptabs = function(o) {

		//Default options
		var s = $.extend({
			dropdownSelector		 	: "li.dropdown",
			dropdownMenuSelector		: "ul.dropdown-menu",
			dropdownTabsSelector		: "li",
			visibleTabsSelector			: ">li:not(.dropdown)",
			developmentId				: "dt-devInfo",
			autoArrangeTabs				: true,
			development					: false
        }, o);

        return this.each( function(  ) {
		
			var $container = $(this);
			var dropdown = $(s.dropdownSelector, this);
			var dropdownMenu = $(s.dropdownMenuSelector, dropdown);
			
			var $dropdownTabs = function () {
				return $(s.dropdownTabsSelector, dropdownMenu);			
			}
			
			var $visibleTabs = function () {
				return $(s.visibleTabsSelector, $container);
			}
			
			function getFirstHiddenElementWidth() {			
				var tempElem=$dropdownTabs().first().clone().appendTo($container).css("position","fixed");
				var hiddenElementWidth = $(tempElem).outerWidth();
				$(tempElem).remove();
				return hiddenElementWidth;
			}
			
			function getHiddenElementWidth(elem) {
				var tempElem=$(elem).clone().appendTo($container).css("position","fixed");
				var hiddenElementWidth = $(tempElem).outerWidth();
				$(tempElem).remove();
				return hiddenElementWidth;
			}
			
			function manageActive(elem) {
				//fixes a bug where Bootstrap can't remove the 'active' class on elements after they've been hidden inside the dropdown
				$('a', $(elem)).on('show.bs.tab', function (e) {
					$(e.relatedTarget).parent().removeClass('active');
				})
				$('a', $(elem)).on('shown.bs.tab', function (e) {
					if ($(dropdown).hasClass('active')) {
						$('>a', dropdown).html(($('>li.active>a', dropdownMenu).html()).substring(0,10) + '... <b class="caret"></b>');
					} else {
						$('>a', dropdown).html('Dropdown <b class="caret"></b>'); 
					}
				})
				
			}
			
			//Start Development info
			if ( s.development ) {		
				$('body').append('<div class="alert alert-success" id="'+ s.developmentId +'"></div>');
				var $developmentDiv = $('#' + s.developmentId);
				$($developmentDiv).css('position','fixed').css('right','20px').css('bottom','20px');				
				function devPrint(label, elem) {
				var labelId = label.replace(/\s+/g, '-').toLowerCase();				
					if ($('#'+labelId).length > 0) {
						$('#'+labelId).text(label + ': ' + elem);
					} else {
						$('#dt-devInfo').append('<div id="' + labelId + '">' + label + ': ' + elem + '</div>');
					}
					return true;
				}
			}		
			//End Development info
			
			var visibleTabsWidth = function () {
				var visibleTabsWidth = 0;
				$($visibleTabs()).each(function( index ) {
					visibleTabsWidth += parseInt($(this).outerWidth(), 10);
				});
				visibleTabsWidth = visibleTabsWidth + parseInt($(dropdown).outerWidth(), 10);
			return visibleTabsWidth;
			}
			
			var availableSpace = function () {
				return $container.outerWidth()-visibleTabsWidth();
			}
			
			var arrangeTabs = function () {
				//Start Development info
				if ( s.development ) {		
					devPrint("Container width", $container.outerWidth());
					devPrint("Visible tabs width", visibleTabsWidth());
					devPrint("Available space", availableSpace());
					devPrint("First hidden", getFirstHiddenElementWidth());
				}
				//End Development info	
				
				if (availableSpace()<0) {//we will hide tabs here
					var x = availableSpace();
					$($visibleTabs().get().reverse()).each(function( index ){
						if (!($(this).hasClass('always-visible'))){	
								$(this).prependTo(dropdownMenu);
								x=x+$(this).outerWidth();
						}  
						if (x>=0) {return false;}
					});
				}
				
				if (availableSpace()>getFirstHiddenElementWidth()) { //and here we bring the tabs out
					var x = availableSpace();
					$($dropdownTabs()).each(function( index ){
						if (getHiddenElementWidth(this) < x && !($(this).hasClass('always-dropdown'))){	
							$(this).appendTo($container);	
							x = x-$(this).outerWidth();
						} else {return false;}
					 });
				}
				
				if ($dropdownTabs().length <= 0) {dropdown.hide();} else {dropdown.show();}
			}
			
			//init
			
			if (s.autoArrangeTabs) {
				var tempTabs = [];
				$($visibleTabs().get().reverse()).each(function( index ){
					if ($(this).hasClass('always-visible')) {
						tempTabs.push($(this));
						$(this).remove();
					}
				});
				for (var i = 0; i < tempTabs.length; i++ ) {
					$container.prepend(tempTabs[i]);
				}
			}
			
			$(document).ready(function(){
				arrangeTabs();
				$dropdownTabs().each( function() {
					manageActive($(this));
				});
				
				$visibleTabs().each( function() {
					manageActive($(this));
				});
			});
			
			$( window ).resize(function() {
				arrangeTabs();
			});
			return this;		
        });
    }
}(jQuery));
