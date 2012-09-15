/*
	Copyright (C) 2012  Darvin Mertsch

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

$(document).ready(function() {
	var getVars = getUrlVars();
	
	$('#wysiwyg').wysiwyg({
	  controls: {
		
	  },
	  events: {
		keyup : function(e) {
			e.preventDefault();
			var code = window.opener.document.getElementById(getVars['dest']);
			
			var editorInput = $('#wysiwyg').val();
			$('body').append('<div id="shadowDiv" style="visibility:hidden;"></div>');
			$('#shadowDiv').html(editorInput);
			
			$('#shadowDiv').find('img').each(function() {
				$(this).attr('src', 'uploads/'+$(this).attr('filename'));
				$(this).removeAttr('filename');
			});
			editorInput = $('#shadowDiv').html();
			$('#shadowDiv').remove();
			$(code).val(editorInput);
		}
	  }
	});
	
	// $(window).bind('beforeunload', function() {
		// var code = window.opener.document.getElementById(getVars['dest']);
		
		// var editorInput = $('#wysiwyg').val();
		// $('body').append('<div id="shadowDiv" style="visibility:hidden;"></div>');
		// $('#shadowDiv').html(editorInput);
		
		// $('#shadowDiv').find('img').each(function() {
			// $(this).attr('src', 'uploads/'+$(this).attr('filename'));
			// $(this).removeAttr('filename');
		// });
		// editorInput = $('#shadowDiv').html();
		// $('#shadowDiv').remove();
		// $(code).val(editorInput);
	// });

	function getUrlVars()
	{
		var vars = [], hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for(var i = 0; i < hashes.length; i++)
		{
			hash = hashes[i].split('=');
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}
		return vars;
	}
});