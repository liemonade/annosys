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

function markupOverview() {
	$('#overview').ready(function() {
		$.getJSON('overview.php?action=getOverview', function(data) {
			if (data.onlinestatus.monitor != undefined) {
				$.each(data.onlinestatus.monitor, function(monitorname, monitordata) {
					var isOnline = false;
					var onlinestatus = 'Offline';
					if (monitordata.lastaccess) {
						isOnline = true;
						var actTimestamp = Math.round(Date.parse(Date()) / 1000);
						if (actTimestamp-60 < monitordata.lastaccess) {
							onlinestatus = 'Online';
						}
						else if (actTimestamp-180 < monitordata.lastaccess) {
							onlinestatus = 'Timeout';
						}
						else {
							onlinestatus = 'LongTimeout';
						}
					}
					$('#monitorOverview').append(
						$('<div class="onlinestatusMonitor"><div>').html(
							'<p class="onlinestatusMonitorText"><font class="username">'+monitorname+'</font> ist <font class="monitor'+onlinestatus+'">'+((isOnline)?('online'):('offline'))+'</font></p>'+
							'<img src="images/'+onlinestatus.toLowerCase()+'.png" class="onlinestatusMonitorImage"/>'+
							'<p class="onlinestatusMonitorLastLogin">Letzte'+((isOnline)?(' Aktualisierung'):('r Login'))+': '+((isOnline)?(toFullGerDate(monitordata.lastaccess)):(toFullGerDate(monitordata.lastlogin)))+'</p>'
						)
					);
				});
			}
			if (data.user != undefined) $('#acpUsername').html(data.user);
			if (data.substitutions.activesubstitutions != undefined) $('#activesubstitutions').html(data.substitutions.activesubstitutions);
			if (data.substitutions.lastimport != undefined) $('#datetimesubstitutionsimport').html(toGerDateTime(data.substitutions.lastimport));
			if (data.extrapages.activepages != undefined) $('#activepages').html(data.extrapages.activepages);
			if (data.tickermessages.activemessages != undefined) $('#activemessages').html(data.tickermessages.activemessages);
			if (data.onlinestatus.schueleronline != undefined) $('#schueleronline').html(data.onlinestatus.schueleronline);
			if (data.users.numschueler != undefined && data.users.schuelerusingsystem != undefined) $('#numschueler').html(data.users.schuelerusingsystem+' von '+data.users.numschueler);
		});
	});
	
	function toFullGerDate(timestamp) {
		var date = new Date();
		date.setTime(timestamp*1000);
		var tag = date.getDate();
		var monat = date.getMonth() + 1;
		var jahr = date.getYear()+1900;
		var stunde = date.getHours();
		var minute = date.getMinutes();
		var sekunde = date.getSeconds();
		return ((tag < 10) ? '0'+tag : tag)+'.'+
			((monat < 10) ? '0'+monat : monat)+'.'+
			jahr+' '+
			((stunde < 10) ? ('0'+stunde) : stunde)+':'+
			((minute < 10) ? ('0'+minute) : minute)+':'+
			((sekunde < 10) ? ('0'+sekunde) : sekunde);
	}
}