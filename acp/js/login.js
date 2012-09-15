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

$(document).ready(function(){
	
	$('table').keydown(function(e){
		if(e.which == 13)	//Enter
			login();
	});
	
	$('#sendLogin').click(function(event) {
		event.preventDefault();
		login();
	});
});

function login() {
	$('#answer').html($('<img />').attr('src', 'images/loader.gif'));
	$('#answer').css({visibility: 'visible'});
	$.post('login.php', {user: $('input:eq(0)').val(), pass: $('input:eq(1)').val()},function(data) {
		var answer = $.parseJSON(data);
		if (answer.loginstatus == 'ok') {
			setTimeout('self.location.href="index.php"', 2000);
			$('#answer').html(
				'<font color="green">Login OK. Sie werden weitergeleitet. Falls dies nicht automatisch geschieht, klicken Sie <a href="index.php">[HIER]</a></font>'
			);
		}
		else {
			$('#answer').html('<font color="red">Login fehlgeschlagen!</font>');
		}
	});
}