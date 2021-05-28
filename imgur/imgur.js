//------------------------------------------------------------------------------------------------------------------------------------
// Загрузка изображения на imgur.com
//------------------------------------------------------------------------------------------------------------------------------------
var clientId = "111";	// cliend id от приложения imgur
var clientSecret = "111";	// client secret от приложения imgur

var imgur = document.createElement("div");
function GetCursorPosition(e) {
	var X = 0;
	var Y = 0;
    if (document.all) {
        X = event.clientX + document.body.scrollLeft;
        Y = event.clientY + document.body.scrollTop;
    }
    else {
        X = e.pageX;
        Y = e.pageY;
    }
	if((document.body.clientWidth-X) < 300) X -= 300;
    imgur.style.left = X + "px";
    imgur.style.top = Y + "px";
    return true;
}

function UploadToImgurWindow(e) {
	GetCursorPosition(e);
	var xhr = new XMLHttpRequest();
	xhr.open("POST", "http://b3d.org.ua/forum/konkurs/getkonkursinfo.php");
	xhr.onload = function() {
		var forumIdStr = JSON.parse(xhr.responseText);
		document.body.appendChild(imgur);
		imgur.id = "ImgurUploadWindow";
		var imgurWindowSrc = "<table border=0 width=350 cellspacing=10><tr><td>Изображение будет загружено на хостинг imgur.com:</td></tr><tr><td><input type=file id=imgurfile accept='image/*'></td></tr><tr><td>Если у Вас есть аккаунт на imgur.com, Вы также можете сохранить изображение в нем (будет открыто дополнительное окно для авторизации на imgur)<br><input type=checkbox id=imguracc>Сохранить в моем аккаунте</input></td></tr>";
		for (i = 0; i < forumIdStr.length; ++i) {
			if(window.location.toString().indexOf(forumIdStr[i][0]) != -1) {
				imgurWindowSrc += "<tr><td><hr></td></tr>";
				imgurWindowSrc += "<tr><td>Если изображение предлагается для участия в конкурсе, предложите новую тему (обязательно):</td></tr>";
				imgurWindowSrc += "<tr><td><input type=text id='newtheme' name='"+forumIdStr[i][1]+"'></td></tr>";
				imgurWindowSrc += "<tr><td><hr></td></tr>";
				break;
			}
		}
		imgurWindowSrc += "<tr><td align=right><input type=submit id='submittoimgur' value='ЗАГРУЗИТЬ' onclick='UploadFileToImgur();'>&nbsp;<input type=submit value='ОТМЕНА' onclick='CloseUploadToImgurWindow();'></td></tr></table>";
		imgur.innerHTML = imgurWindowSrc;
	}
	xhr.send();
}

function UploadFileToImgur() {
	if(document.getElementById('imgurfile').files[0]) {
		document.getElementById('submittoimgur').disabled = true;
		if(document.getElementById("imguracc").checked == false) {
			uploadFile();
		}
		else {
			var imgur_token = getCookie("imgur_token");
			if(imgur_token) {
				if(getCookie("imgur_expires") >= (new Date().getTime())) uploadFile(imgur_token);
				else {
					uploadFileWithRefreshToken(getCookie("imgur_refresh_token"), clientId, clientSecret);
				}
			}
			else {
				var wnd = window.open("https://api.imgur.com/oauth2/authorize?client_id="+clientId+"&response_type=token","_blank");
				var timer = setInterval(function() {
					if(wnd.closed == true) {
						clearInterval(timer);
						return;
					}
					var isHash = -1;
					try {
						isHash = wnd.location.href.indexOf("#");
					}
					catch(e) {
						if(e.code != 18) throw(e);
					}
					if(isHash > -1) {
						var redirectedUrl = wnd.location.hash.substring(1).split("&");
						imgur_token = (redirectedUrl[0].split("="))[1];
						saveCookie(imgur_token, (redirectedUrl[3].split("="))[1], (new Date().getTime())+((redirectedUrl[1].split("="))[1])*1000);
						clearInterval(timer);
						wnd.close();
						uploadFile(imgur_token);
					}
				}, 500);
			}
		}
	}
	else CloseUploadToImgurWindow();
}

function CloseUploadToImgurWindow() {
	document.body.removeChild(document.getElementById(imgur.id));
}

function uploadFile(vToken) {
	var xhr = new XMLHttpRequest();
	var fd = new FormData();
	fd.append("image", document.getElementById('imgurfile').files[0]);
	xhr.open("POST", "https://api.imgur.com/3/image.json");
	xhr.onload = function() {
		var responce = JSON.parse(xhr.responseText);
		console.log(responce);
		if(responce.success == false) {
			if(responce.data.error == "The access token provided is invalid.") {
				clearCookies();
				UploadFileToImgur();
			}
		}
		else {
			var fileid = JSON.parse(xhr.responseText).data.id;
			var lnk = JSON.parse(xhr.responseText).data.link;
			var fileext = lnk.substr(lnk.lastIndexOf(".") + 1);
			var code = "[url=http://i.imgur.com/"+fileid+"."+fileext+"][img]http://i.imgur.com/"+fileid+"m."+fileext+"[/img][/url]";
			if(document.getElementById('newtheme') && document.getElementById('newtheme').value) {
				if(document.getElementById('newtheme').getAttribute('name')) {
					//[KONKURS TYPE={TEXT1}; IMG={TEXT2}; PREVIEW={TEXT3}]{TEXT4}[/KONKURS]
					code = code+"\r\n"+"[KONKURS TYPE="+document.getElementById('newtheme').getAttribute('name')+"; IMG=http://i.imgur.com/"+fileid+"."+fileext+"; PREVIEW=http://i.imgur.com/"+fileid+"m."+fileext+"]"+document.getElementById('newtheme').value+"[/KONKURS]";
				}
			}
			document.getElementById("message").value = document.getElementById("message").value+"\r\n"+code;
			CloseUploadToImgurWindow();
		}
	}
	if(vToken) xhr.setRequestHeader('Authorization', 'Bearer '+vToken);
	else xhr.setRequestHeader('Authorization', 'Client-ID '+clientId);
	xhr.send(fd);
}

function uploadFileWithRefreshToken(vRefreshToken, vClientId, vClientSecret) {
	var xhr = new XMLHttpRequest();
	var params = 'refresh_token=' + encodeURIComponent(vRefreshToken) + '&client_id=' + encodeURIComponent(vClientId) + '&client_secret=' + encodeURIComponent(vClientSecret) + '&grant_type=refresh_token';
	xhr.open("POST", "https://api.imgur.com/oauth2/token");
	xhr.onload = function() {
		var new_imgur_token = JSON.parse(xhr.responseText).access_token;
		var new_imgur_expires = JSON.parse(xhr.responseText).expires_in;
		var new_imgur_refresh_token = JSON.parse(xhr.responseText).refresh_token;
		saveCookie(new_imgur_token, new_imgur_refresh_token, new_imgur_expires);
		uploadFile(new_imgur_token);
	}
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
	xhr.send(params);
}

function saveCookie(vNewToken, vNewRefreshToken, vNewExpires) {
	var date = new Date(new Date().getTime() + 2592000000);	// cookie на 30 дней (в мс)
	document.cookie="imgur_token=" + vNewToken + "; path=/; expires="+date.toUTCString();
	document.cookie="imgur_expires=" + vNewExpires + "; path=/; expires="+date.toUTCString();
	document.cookie="imgur_refresh_token=" + vNewRefreshToken + "; path=/; expires="+date.toUTCString();
}

function getCookie(vName) {
	var matches = document.cookie.match(new RegExp("(?:^|; )" + vName.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}

function clearCookies() {
	var date = new Date(new Date().getTime() -1);
	document.cookie="imgur_token=; path=/; expires="+date.toUTCString();
	document.cookie="imgur_expires=; path=/; expires="+date.toUTCString();
	document.cookie="imgur_refresh_token=; path=/; expires="+date.toUTCString();
}