<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

if(!defined('INSTAGRAM_PLUGIN_URL')) {
  define('INSTAGRAM_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
}

?>

<!DOCTYPE html>
<html>
<head>
	<title>How to get your Instagram Client ID</title>
	<style type="text/css">
		body, html {
			font-family: arial, sans-serif;
			padding: 10px;
		}

		dl dt {
			float: left;
			clear: left;
			width: 150px;
		}

		dl dd {
			float: left;
		}

		dl {
			clear: both;
			overflow: hidden;
		}

		li {
			padding: 20px 0;
			border-bottom: 1px solid #bbb;
		}
	</style>
</head>
<body>
	<h1>How to get your Instagram Client ID</h1>

	<ol>
		<li>
			Visit <a href="http://instagram.com/developer" target="_blank">http://instagram.com/developer</a>
			and click on Manage Clients (top right hand corner)
			<br>
			<br>
			<img src="img/1.png">
		</li>

		<li>
			Click on the Register a New Client button (top right hand corner again)
			<br>
			<br>
			<img src="img/2.png">
		</li>

		<li>
			Fill in the register new OAuth Client form with:

			<dl>
				<dt>Application name</dt>
				<dd>Name of your website</dd>
				
				<dt>Description</dt>
				<dd>Instagram wordpress plugin</dd>
				
				<dt>Website</dt>
				<dd>Your website url</dd>
				
				<dt>OAuth redirect_url</dt>
				<dd><?php echo INSTAGRAM_PLUGIN_URL . '/authenticationhandler.php'; ?></dd>

			</dl>

			<br>
			<br>
			<img src="img/3.png">
		</li>
	</ol>

</body>
</html>