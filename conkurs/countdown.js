//------------------------------------------------------------------------------------------------------------------------------------
// Счетчик обратного отсчета
// Вызов:	<script end="2015-07-25 22.00" period=86400000 type="text/javascript" src="countdown.js"></script>
// 86400000 = одни сутки, ставить можно любые значения для задания периодического отсчета
// время окончания 2015-07-25 22.00 - выставляется по времени сервера
//------------------------------------------------------------------------------------------------------------------------------------
var scripts = document.getElementsByTagName('script');
var serverScript = scripts[scripts.length-1].getAttribute("serverScript");
document.write("<table border=1><tr><td name=countdown end=\""+scripts[scripts.length-1].getAttribute("end")+"\"");
if(scripts[scripts.length-1].getAttribute("period") != null) document.write("period="+scripts[scripts.length-1].getAttribute("period"));
document.write("></td></tr></table>");
if(!xhr) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST", serverScript+"?cache="+(new Date()).getTime());
	xhr.onload = function() {
		var server_time = xhr.responseText;
		var local_time = (new Date()).getTime()- (new Date()).getTimezoneOffset()*60*1000;
		var delta = server_time - local_time;
		setInterval(
			function(delta) {
				var countdowns = document.getElementsByName("countdown");
				for (var i = 0; i < countdowns.length; i++) {
					var endTime = new Date(countdowns[i].getAttribute("end").replace(/-/g,'/').replace(/\./g,':')).getTime();
					var currentTime = (new Date()).getTime() + delta;	// текущее время с поправкой на сервер
					if(countdowns[i].getAttribute("period") != null) {
						var period = Number(countdowns[i].getAttribute("period"));
						if(period > 0) {
							while((endTime-currentTime) < 0) {
								endTime += period;
							}
						}
					}
					var diff = endTime - currentTime;
					var sign = "";
					if(diff < 0) sign = " -&nbsp;";
					var diff = Math.abs(diff);
					var dDays = Math.floor(diff/1000/60/60/24);
					diff -= dDays*24*60*60*1000;
					var dHours = Math.floor((diff)/1000/60/60);
					diff -= dHours*60*60*1000;
					var dMinutes = Math.floor((diff)/1000/60);
					diff -= dMinutes*60*1000;
					var dSeconds = Math.floor((diff)/1000);
					countdowns[i].innerHTML = "&nbsp;" + sign + (dDays > 0 ? dDays + " д. ":"") + (dHours > 0 ? dHours + " ч. ":"") + (dMinutes > 0 ? dMinutes + " м. ":"") + dSeconds + ' с.' +"&nbsp;";
				}
			}
			, 1000, delta
		);
	}
	xhr.send(null);
}