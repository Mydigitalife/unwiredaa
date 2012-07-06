var callbackUrl, uploadUrl;

function reloadFiles() {
	$.ajax({
		url: callbackUrl,
		/*dataType: 'jsonp',*/
		cache: false,
		success: function(data) {
			var ulist = $('<div class="files"></div>');
			$.each(data, function (idx, file){
				$(ulist).append('<input id="file_' + idx +'" type="radio" name="selected_file" value="'+ file.path + '" /><label for="file_' + idx +'"><img src="' + file.path + '" alt="'  + file.name + '" /></label>');
			});
			
			$('#fileBrowser').find('.fileList').html(ulist);
			$('#fileBrowser').find('.fileList .files').buttonset();
			$('#fileBrowser').find('.fileList .files label:first').removeClass('ui-corner-left');
			$('#fileBrowser').find('.fileList .files label:last').removeClass('ui-corner-right');
			$('#fileBrowser').find('.fileList .files label').addClass('ui-corner-all');
			
			$('#fileBrowser').find('.fileList .files label').tipsy({
				trigger: 'manual',
				title: function() {
					return '<div class="tools file_toolbar"><input type="hidden" name="path" value="' + $(this).prev().val() + '" /><span class="filename">' + $(this).find('img').attr('alt') + '</span>' +
						   '<a class="icon edit" title="Rename file">Rename</a><a class="icon delete" title="Delete file">Delete</a></div>';
					
				},
				html: true,
			});
			
			$('.tipsy').remove();
		}
	});
	
	$('#uploader_frame').remove();
}

function renameFile()
{
	var filePath = $(this).siblings('input[type=hidden]').val();
	var fileName = $(this).siblings('.filename').text();
	
	if (!confirm("Are you sure you want to RENAME the file '" + fileName + "'?\n\n" +
				 "WARNING: Renaming a file from splashpage/template may cause distortions in pages in which this file is used!")) {
			return false;
	}
	
	var newFileName = prompt('Enter new file name (without extension):', fileName.replace(/\..*?$/gi, ''));
	
	if (!newFileName) {
		return false;
	}
	
	var regex = new RegExp('^[a-z\ 0-9\_\-]+$','gi');
	
	if (!regex.test(newFileName)) {
		alert('Invalid file name! File name should contain only letters, numbers, -, _ or space.',1);
		return false;
	}

	$.ajax({
		url: callbackUrl,
		cache: false,
		data: {
			cmd: 'rename',
			file: fileName,
			new_name: newFileName
		},
		success: function(data) {
			if (data.length != 1) {
				alert('There was an error while renaming the file. File does not exist or you do not have permissions.',1);
				return false;
			}
			
			var radio = $('#fileBrowser label span img[alt="' + fileName + '"]').parents('label').prev();

			$(radio).val($(radio).val().replace(fileName, data[0].name));
			$(radio).next().find('img').attr('alt', data[0].name).attr('src', $(radio).val());
			$('.tipsy').remove();
			if (!$(radio).is(':checked')) {
				$(radio).next().click();
			} else {
				$(radio).change();
			}
		},
		error: function(data) {
			alert('There was an error while renaming the file. File does not exist or you do not have permissions.',1);
		}
	})
	return false;
}

function deleteFile()
{
	var filePath = $(this).siblings('input[type=hidden]').val();
	var fileName = $(this).siblings('.filename').text();
	if (!confirm("Are you sure you want to DELETE the file '" + fileName + "'?\n\n" +
				 "WARNING: Deleting a file from splashpage/template may cause distortions in pages in which this file is used!")) {
		return false;
	}

	$.ajax({
		url: callbackUrl,
		cache: false,
		data: {
			cmd: 'delete',
			file: fileName,
		},
		success: function(data) {
			if (data.length != 1) {
				alert('There was an error while deleting the file. File does not exist or you do not have permissions.',1);
				return false;
			}
			
			var radio = $('#fileBrowser label span img[alt="' + fileName + '"]').parents('label').prev();

			$('.tipsy').remove();
			$(radio).next().remove();
			$(radio).remove();
		},
		error: function(data) {
			alert('There was an error while deleting the file. File does not exist or you do not have permissions.',1);
		}
	})
	return false;
}

function createFileBrowser(cbkUrl, uplUrl)
{
	callbackUrl = cbkUrl;
	uploadUrl = uplUrl;
	
	if($('#fileBrowser').length > 0) {
		return;
	}
	

	$('body').append('<div id="fileBrowser"><div class="fileList"></div><div class="fileUpload"><form method="post" action="'
					 +uploadUrl+'" enctype="multipart/form-data"></form></div></div>');
	
	var fileBrowser = $('#fileBrowser').hide();
	
	$(fileBrowser).find('form').append('<label>File :</label><input type="file" name="file_upload" /> <span class="button small blue"><input type="submit" value="Upload" />');
	
	$(fileBrowser).find('form input[type=submit]').click(function(){
		$(fileBrowser).append('<iframe id="uploader_frame" name="uploader_frame" style="width: 0px; height: 0px; display: none"></iframe>');
		
		$(this).parents('form:first').attr('target', 'uploader_frame');
		
		$('#uploader_frame').load(function(){
			//alert($(document, frames['uploader_frame']).text());
			reloadFiles();
		});
	});
	
	$(fileBrowser).dialog({
		modal: true,
		title: 'File browser',
		draggable: false,
		autoOpen: false,
		width: 'auto',
		height: 'auto',
		zIndex: 15000,
		close: function() {
			$('.tipsy').remove();
		},
		create: function() {
			reloadFiles();
		},
		buttons: {
			'Cancel': function() {
				$(this).dialog('close');
			},
		}
	});
	
	$('#fileBrowser').find('.fileList .files input[type=radio]').live('change', function(){
		if ($(this).is(':checked')) {
			$(this).siblings('label').each(function() {$(this).tipsy("hide") });
			$(this).next().tipsy('show');
			
			$('.file_toolbar a.icon').unbind('click')
									 .die('click');
			$('.file_toolbar a.icon.edit').click(renameFile);
			$('.file_toolbar a.icon.delete').click(deleteFile);
		}
	});
}

function openFileBrowser(elem, prefix)
{
	var buttons = $('#fileBrowser').dialog('option', 'buttons');
	
	if (!prefix) {
		prefix = '';
	}
	
	buttons['Select'] = function() {
		if (!$('#fileBrowser').find('input[type=radio]:checked').length) {
			return;
		}
		$(elem).val(prefix + $('#fileBrowser').find('input[type=radio]:checked').val());
		$(elem).change();
		$(this).dialog('close');
	}
	
	$('#fileBrowser').dialog('option', 'buttons', buttons)
					 .dialog('open');
}