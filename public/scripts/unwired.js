/**
 * Override alert()
 */
(function() {
  var proxied = window.alert;
  window.alert = function() {
	if (arguments.length == 2) {
		proxied(arguments[0]);
		return;
	}
	$('body').append('<div id="alertoverride">' + arguments[0] + '</div>');
	$('#alertoverride').dialog({
		modal: true,
		draggable: false,
		resizable: false,
		zIndex: 19999,
		title: 'Alert',
		buttons: { "Ok": function() { $(this).dialog("close"); } },
		close: function(event, ui) { 
			$('#alertoverride').dialog("destroy");
			$('#alertoverride').remove();
		}
	});
  };
})();

/**
 * Check if string is valid email address
 * @param str
 * @returns {Boolean}
 */
function email_check(str) {

	var at="@"
	var dot="."
	var lat=str.indexOf(at)
	var lstr=str.length
	var ldot=str.indexOf(dot)
	if (str.indexOf(at)==-1){
	   return false;
	}

	if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr){
	   return false;
	}

	if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr){
	    return false;
	}

	 if (str.indexOf(at,(lat+1))!=-1){
	    return false;
	 }

	 if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
	    return false;
	 }

	 if (str.indexOf(dot,(lat+2))==-1){
	    return false;
	 }

	 if (str.indexOf(" ")!=-1){
	    return false;
	 }

	 return true;
}

/**
 * Initialize common events
 */
$(document).ready(function(){
	/**
	 * Hide empty elements from navigation
	 */
	$('ul.navigation li a[href="javascript:;"]').each(function(){
		if (!$(this).siblings().length) {
			$(this).parent().remove();
		}
	});
	
	/**
	 * Hook delete buttons
	 */
	$('.tools a.delete, .portlet-header a.delete').live('click', function(){
		return confirm('Are you sure you want to delete this entry?');
	});
	
	/**
	 * Check if node is selectable
	 */
	$('.grouptree').bind("before.jstree", function(event, data) {
    	if (data.func != 'select_node') {
    		return;
    	}

    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	/**
    	 * If selecting children is disabled check and show alert
    	 */
    	if ($(this).jstree("get_settings").ui.disable_selecting_children == true
    		/*&& typeof data.args[0] == 'object'*/ && !$(data.args[0]).hasClass('jstree-clicked')) {
    		
    		var found = false;
    		
    		$(data.args[0]).parents('.jstree-open').each(function () {
    			if ($(this).children('a.jstree-clicked').length > 0) {
    				alert('There is a parent group selected! To be able to select child group you need to deselect the parent first.');
    				found = true;
    				return false;
    			}
    		});
		
    		if (found) {
    			event.stopImmediatePropagation();
    	  		return false;
    		}
    		
			if ($(data.args[0]).parent().find('a.jstree-clicked').length > 0) {
				alert('There are child groups selected! To be able to select parent group you need to deselect its children first.');
				event.stopImmediatePropagation();
		  		return false;
			}
    	}
    	
    	if (typeof adminGroups == 'undefined') {
    		// When tree is present only for selection. No admin groups required!
    		return;
    	}
	
    	/**
    	 * Check if current admin user has group privileges
    	 */
		var groupId;
		if (typeof data.args[0] == 'string') {
			groupId = data.args[0].replace(/[^\d]+/gi, '');
		} else {
			groupId = $(data.args[0]).parent().attr('id').replace(/[^\d]+/gi, '');
		}
		
    	event.result = false;
		$.each(adminGroups, function(idx, role){
		  	if (idx == groupId || $(data.args[0]).parents('#group_' + idx).length > 0) {
		  		event.result = undefined;
		  		return false;
		  	}
		});
		
	  	if (event.result == false) {
	  		alert('You don\'t have permissions to assign to that group!');
	  		event.stopImmediatePropagation();
	  		return false;
	  	}
    });
	
	$('table.listing thead tr th input').keyup(function(event){
		if (event.keyCode != '13') {
			return;
		}
		
		$('table.listing thead tr th a.filter:last').click();
		return false;
	});
	
	$('table.listing thead tr th a.filter:last').click(function(){
		var url = $(this).parents('form:first').attr('action');
		
		var action = $(this).attr('name').replace(/\_/gi, '/');

		var regexp = new RegExp(action.replace('/', '\/'), 'gi');
		if (action.length && !regexp.test(url)) {
			url = url + '/' + action;
		}
		
		$(this).parents('tr:first').find('th input, th select').each(function(){
			var attr_name = $(this).attr('name');
			var value = $(this).val().replace(/[^a-z0-9ÄÖÜäöüßêñéçìÈùø\s\@\-\:\.]+/gi, '');
			
			if (!value.length) {
				return true;
			}
			url = url + '/' + attr_name + '/' + value;
		})
		
		window.location.href=url;
	});
	
	$('table.listing thead tr th a.reset:last').click(function(){
		$(this).parents('tr:first').find('th input, th select').each(function(){
			$(this).val('');
		})
		
		$('table.listing thead tr th a.filter:last').click();
		
		return false;
	});
	
	$('.tools a.icon, .portlet-header a').tipsy({
		delayIn: 500,
		delayOut: 0,
		title: function() { return $(this).text(); }, 
		fade: true,
		opacity: 0.8
	});
	
	$('.tip').tipsy({
		delayIn: 500,
		delayOut: 0,
		fade: true,
		opacity: 0.8
	});
	
	/*if (uiLanguage == 'en') {
		$.datepicker.setDefaults($.datepicker.regional['']);
		$.timepicker.setDefaults($.datepicker.regional['']);
	} else if ($.datepicker.regional[uiLanguage]) {
		$.datepicker.setDefaults($.datepicker.regional[uiLanguage]);
		$.timepicker.setDefaults($.datepicker.regional[uiLanguage]);
	}*/
	$('.datepicker').datepicker({
		dateFormat: 'dd.mm.yy'
		
	});
	$('.datetimepicker').datetimepicker({
		dateFormat: 'dd.mm.yy',
		timeFormat: 'hh:mm'
	});
});