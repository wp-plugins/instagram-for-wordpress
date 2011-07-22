jQuery(document).ready(function($) {
	$("ul.wpinstagram").cycle({fx: "fade"});
	$("ul.wpinstagram").find("a").fancybox({
		"transitionIn":			"elastic",
		"transitionOut":		"elastic",
		"easingIn":			"easeOutBack",
		"easingOut":			"easeInBack",
		"titlePosition":		"over",
		"padding":			0,
		"hideOnContentClick":		"true"
	});
});
