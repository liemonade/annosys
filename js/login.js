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
	
	$('#sendLogin').live('click', login);
});

function login() {
	var loginForm = $('#Login').html();
	var user = $('#user').val();
	var pass = $('#pass').val();
	
	$('#Login').html($('<img />').attr('src', 'images/ajax-loader.gif'));
	$.post('login.php?action=login', {user: user, pass: pass}, function(data) {
		var answer = $.parseJSON(data);
		if (answer.loginstatus != 'ok') {
			$('#Login').animate({height: 80, width: 80, marginTop: -39, marginLeft: -39}, 500, function(){
				$(this).html($('<img />').attr('src', 'images/fail.png'));
			});
			$('#Login').delay(2000).animate({height: 80, width: 250, marginTop: -50, marginLeft: -135}, 500, function(){
				$('#Login').html(loginForm);
			});
			return;
		}
		$('#Login').animate({height: 80, width: 80, marginTop: -39, marginLeft: -39}, 500, function(){
				$(this).html($('<img />').attr('src', 'images/ok.png'));
		});
		$('#Login').delay(2000).animate({height: 135, width: 250, marginTop: -87, marginLeft: -135}, 500, function(){
			$(this).html($('#Settings').html());
		});
		
		var minGrade = parseInt(answer.minGrade);
		var maxGrade = parseInt(answer.maxGrade);
		for (var grade = minGrade; grade <= maxGrade; grade++) {
			$('#selVon').append( $('<option></option>').html(grade) );
			$('#selBis').append( $('<option></option>').html(grade) );
		}
		
		$('#selVon option:first').attr('selected', 'selected');
		$('#selBis option:last').attr('selected', 'selected');
		
		$('#selVon').live('click', function(){
			var selectedVon = Number($('#selVon option:selected').text());
			var selectedBis = Number($('#selBis option:selected').text());
			var options = '';
			for(i = selectedVon; i <= maxGrade; i++)
				options += '<option>'+i+'</options>';
			$('#selBis').html(options);
			if(selectedBis > selectedVon) {
				var toSelectIdx = selectedBis - selectedVon;
				$('#selBis option:eq('+toSelectIdx+')').attr('selected', 'selected');
			}
		});
		
		outputprofiles = answer.outputprofiles;
		$.each(outputprofiles, function(idx, outputprofile){
			$('#selOutputprofile').append(
				$('<option></option>')
				.attr('value', outputprofile.profileid)
				.html(outputprofile.profilename)
			);
		});
		
		$('#sendSettings').live('click', function(){
			var mingradeVal = $('#selVon option:selected').html();
			var maxgradeVal = $('#selBis option:selected').html();
			var outputprofileVal = $('#selOutputprofile option:selected').val();
			var saveVal = ($('#save:checked').size()?true:false);
			var options = {action:'setSettings', minGrade:mingradeVal, maxGrade:maxgradeVal, outputprofileid:outputprofileVal, save:saveVal};
			$.getJSON('login.php', options, function(data){
				if (data.status != 'success') {
					alert('Es ist ein Fehler aufgetreten');
					return;
				}
				if (saveVal) {
					setCookie('USER', user);
					setCookie('PASS', SHA1(pass));
				}
				
				$('#Login').animate({height: 40, width: 400, marginTop: -30, marginLeft: -210}, 500, function(){
					$(this).html(
						'<center><font color="green">Login OK. Sie werden weitergeleitet. Falls dies nicht automatisch geschieht, klicken Sie <a href="index.php">[HIER]</a></font></center>'
					)
					setTimeout('self.location.href="index.php"', 2000);
				});
			});
		});
	});
}

function setCookie(name, value) {
	var expire = new Date();     //set new date object
	expire.setTime(expire.getTime() + (365 * 24 * 60 * 60 * 1000));
	document.cookie = name + "=" + escape(value) 
	+ "; path=/" 
	+ ((expire == null) ? "" : "; expires=" + expire.toGMTString());
}