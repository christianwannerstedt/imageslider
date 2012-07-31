;(function ($, window, document, undefined) {

	$.fn.imslSlideShow = function(options){
		var slides = [],
			current_id = 0,
			slide_width = options.width || $(this).data("slide-width") || 800,
			slide_height = options.height || $(this).data("slide-height") || 600,
			duration = options.duration || $(this).data("transition-time") || 1000,
			easing = options.easing || $(this).data("easing") || "swing",
			animating = false,
			_this = this;
		
		$(".imsl-slide", this).each(function(i){
			slides.push( $(this) );
		});

		$(".imsl-arrow-left", this).click(function(){
			var next_id = (current_id > 0) ? current_id - 1 : slides.length - 1;			
			animateSlides(next_id, true);
		});
		$(".imsl-arrow-right", this).click(function(){
			var next_id = (current_id < slides.length - 1) ? current_id + 1 : 0;
			animateSlides(next_id, false);
		});

		function animateSlides(next_id, pos_slide){
			if (animating) return;
			animating = true;

			var current_slide = slides[current_id],
				next_slide = slides[next_id];

			next_slide.css("left", (pos_slide ? "-" : "+") + slide_width +"px").add( current_slide ).animate({
				left: (pos_slide ? "+=" : "-=") + slide_width
			}, duration, easing, function(){				
				current_id = next_id;
				$(".imsl-dots li.active", _this).removeClass("active");
				$("#imsl-dot-"+ slides[current_id].attr("id").split("-")[1]).addClass("active");
				animating = false;
			});
		}

		return this;
	}

	$(document).ready(function($){
		
		$(".imsl-slide-show").each(function(i){
			$(this).imslSlideShow({});
		});

	});

}(jQuery, window, document));