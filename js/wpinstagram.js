jQuery.noConflict()(function(){
	jQuery("ul.wpinstagram").cycle({fx: "fade"});
	jQuery("ul.wpinstagram").find("a").fancybox({
		"transitionIn":			"elastic",
		"transitionOut":		"elastic",
		"easingIn":				"easeOutBack",
		"easingOut":			"easeInBack",
		"titlePosition":		"over",
		"padding":				0,
		"hideOnContentClick":	"true"
	});
});