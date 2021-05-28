$(window).load (
	function(){
		$('.ekSliderBlock').ekSlider();
	}
);
$(window).unload (
	function(){
		$('.ekSliderBlock').ekSlider('destroy');
	}
);
//$(document).click (
//	function(){
//		$('.ekSliderBlock').ekSlider('destroy');
//	}
//);
(function($){
	
	var methods = {
		init: function(options) {
			return this.each(function(){
				if($(this).data('ekSliderInit') == true) return this;
				else {
					$.ajax({
						type: "POST",
						url: $(this).attr('getfrom'),
						data: "forSlider=1",
						context: $(this),
						success: function(responce) {
							var konkursArr = JSON.parse(responce);
							var konkursList = '<ul>';
							var navigateList = "<div class=ekSliderNavigate>";
							$.each(konkursArr, function(i, value) {
								konkursList += '<li><a href=' + value[0] + '><table><tr><td><img class=ekSliderImg src=' + value[2] + '></td></tr></table><table class=ekSliderInfoText><tr><td><b>' + value[1] + '</b><hr width=90%><p><i>Прием работ на тему: "' + value[3] + '"</i></td></tr></table><table class=ekSliderAutor><tr><td align=right><i>' + value[4] + '</i></td></tr></table></a></li>';
								navigateList += '<span id=' + i + '></span>';
							});
							konkursList += '</ul>';
							navigateList += '</div>';
							obj = this;
							obj.append(konkursList);
							obj.append(navigateList);
							
							obj.children('ul').css('width', obj.attr('width')*Object.keys(konkursArr).length);
							
							obj.children(".ekSliderNavigate").bind('click', methods.showSlideById);
							obj.children(".ekSliderNavigate").bind('mouseenter', methods.stopAnimate);
							obj.children(".ekSliderNavigate").bind('mouseleave', methods.continueAnimate);
							
							if(((window.CSS && window.CSS.supports) || window.supportsCSS) && CSS.supports("(-webkit-transition: margin-left) or (transition: margin-left) or (-moz-transition: margin-left) or (-o-transition: margin-left)")) {
								obj.children('ul').bind('transitionend', function () {
									$(this).parent().data('isAnimating', false);
								});
							}
							
							obj.data('currentShown', 0);
							obj.children('.ekSliderNavigate').children('#'+obj.data('currentShown')).attr("shown", true);
							obj.data('playAnimation', true);
							obj.data('timer', setInterval(function(){
								showSlide(arguments[0]);
							}, obj.attr('scrolldelay'), obj));

							obj.data('ekSliderInit', true);
						}
					});
				}
			});
		},
		showSlideById: function(event) {
			$(this).parent().data('playAnimation', true);
			$(this).parent().children('ul').stop();
			$(this).parent().data('isAnimating', false);
			showSlide($(this).parent(), event.target.id);
			$(this).parent().data('playAnimation', false);
		},
		stopAnimate: function() {
			$(this).parent().data('playAnimation', false);
		},
		continueAnimate: function() {
			$(this).parent().data('playAnimation', true);
		},
		destroy: function() {
			return this.each(function(){
				$(this).data('ekSliderInit', false);
				$(this).data('isAnimating', false);
				clearInterval($(this).data('timer'));
				$(this).children(".ekSliderNavigate").unbind('click', methods.showSlide);
				$(this).children(".ekSliderNavigate").unbind('mouseenter', methods.stopAnimate);
				$(this).children(".ekSliderNavigate").unbind('mouseleave', methods.continueAnimate);
				$(this).children(".ekSliderNavigate").unbind('transitionend');
			});
		}
	};
	
	function showSlide(obj, number) {
		if(obj.data('playAnimation')) {
			if(!obj.data('isAnimating')) {
				obj.data('isAnimating', true);
				if(number) {
					obj.data('currentShown', number);
				}
				else {
					if(obj.data('currentShown') <  (parseInt(obj.children('ul').css('width')) / parseInt(obj.attr('width'))) - 1) {
						obj.data('currentShown', parseInt(obj.data('currentShown'))+1);
					}
					else {
						obj.data('currentShown', 0);
					}
				}
				obj.children('.ekSliderNavigate').children('[shown=true]').removeAttr("shown");
				obj.children('.ekSliderNavigate').children('#'+obj.data('currentShown')).attr("shown", true);
				pos = parseInt(obj.data('currentShown')) * obj.attr('width');

				if(((window.CSS && window.CSS.supports) || window.supportsCSS) && CSS.supports("(-webkit-transition: margin-left) or (transition: margin-left) or (-moz-transition: margin-left) or (-o-transition: margin-left)")) {
					obj.children('ul').css("margin-left", "-"+pos+"px");
				}
				else {
					obj.children('ul').animate({marginLeft: "-"+pos}, 500, 'linear', function() {
						obj.data('isAnimating', false);
					});
				}
			}
		}
	}
	
	$.fn.ekSlider = function(method) {
		if(methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		}
		else if(typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		}
		else {
			$.error('Undefined method: ' +  method);
		}
	};
}
)(jQuery);