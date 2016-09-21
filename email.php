<?php
# Don't put anything above the previous line, not even blank space

# Copyright 2007, Thomas Boutell and Boutell.Com, Inc. You
# MAY use this code in your own projects. You MAY NOT
# represent this code as your own work. If you wish to share
# this code with others, please do so by sharing the
# following URL:
#
# 
# See: http://www.boutell.com/newfaq/creating/accounts.html

#require "login.php";

require '/home/boutell/html/tools/accountable/login.php';

# OPTIONAL: use my Captcha system to prevent automated
# abuse of the contact form.

#require "captcha.php";
require '/home/boutell/html/tools/captcha/captcha.php';

# The person who receives the email messages
#$recipient = 'example@example.com';
$recipient = 'sipho.ndhlovu@outlook.com';


$serverName = 'www.boutell.com';

if ($_POST['send']) {
	sendMail();
} elseif (($_POST['cancel']) || ($_POST['continue'])) {
	redirect();
} else {
	displayForm(false);
}

function displayForm($messages)
{
	# Import $login object from accountable. If we're not using
	# accountable this is null, which is not a problem
	global $login;

	$escapedEmail = htmlspecialchars($_POST['email']);
	$escapedRealName = htmlspecialchars($_POST['realname']);
	$escapedSubject = htmlspecialchars($_POST['subject']);
	$escapedBody = htmlspecialchars($_POST['body']);
	$returnUrl = $_POST['returnurl'];
	if (!strlen($returnUrl)) {
		# We'll return the user to the page they came from
		$returnUrl = $_SERVER['HTTP_REFERER'];
		if (!strlen($returnUrl)) {
			# Stubborn browser won't give us a referring
			# URL, so return to the home page of our site instead
			$returnUrl = '/';
		}
	}
	$escapedReturnUrl = htmlspecialchars($returnUrl);
	# Shift back into HTML mode to send the form
?>
<html>
<head>
<?php
	if ($login) {
?>
<link href="/accountable/chrome/login.css" rel="stylesheet" type="text/css">
<?php
	}
?>
<title>Contact Us</title>
</head>
<body>
<?php
	# Display Accountable login prompt if we are
	# using Accountable
	if ($login) {
		$login->prompt();
		# Fetch email address and real name from Accountable.
		if (!strlen($escapedEmail)) {
			$escapedEmail = htmlspecialchars($_SESSION['email']);
		}
		if (!strlen($escapedRealName)) {
			$escapedRealName = htmlspecialchars($_SESSION['realname']);
		}
	}
?>
<h1>Contact Us</h1>
<?php
	# Shift back into PHP mode for a moment to display
	# the error message, if there was one
	if (count($messages) > 0) {
		$message = implode("<br>\n", $messages);
		echo("<h3>$message</h3>\n");
	}
?>
<form method="POST" action="<?php echo $_SERVER['DOCUMENT_URL']?>">
<p>
<input 
	name="email" 
	size="64" 
	maxlength="64" 
	value="<?php echo $escapedEmail?>"/>
	<b>Your</b> Email Address
</p>
<p>
<input 
	name="realname" 
	size="64" 
	maxlength="64" 
	value="<?php echo $escapedRealName?>"/>
	Your Real Name (<i>so our reply won't get stuck in your spam folder</i>)
</p>
<p>
<input 
	name="subject" 
	size="64" 
	maxlength="64"
	value="<?php echo $escapedSubject?>"/> 
	Subject Of Your Message
</p>
<p>
<i>Please enter the text of your message in the field that follows.</i>
</p>
<textarea 
	name="body" 
	rows="10" 
	cols="60"><?php echo $escapedBody?></textarea>
<?php
	# Display the captcha if we're using my captcha.php system
	# and the user is not logged in to an Accountable account
	if ((!$_SESSION['id']) && (function_exists('captchaImgUrl'))) {
?>
<p>
<b>Please help us prevent fraud</b> by entering the code displayed in the 
image in the text field. Alternatively,
you may click <b>Listen To This</b> to hear the code spoken aloud.
</p>
<p>
<img style="vertical-align: middle" 
	src="<?php echo captchaImgUrl()?>"/> 
	<input name="captcha" size="8"/> 
	<a href="<?php echo captchaWavUrl()?>">Listen To This</a>
</p>
<?php
	}
?>
<p>
<input type="submit" name="send" value="Send Your Message"/>
<input type="submit" name="cancel" value="Cancel - Never Mind"/>
</p>
<input 
	type="hidden"
	name="returnurl" 
	value="<?php echo $escapedReturnUrl?>"/>
</form>
</body>
</html>
<?php
}

function redirect()
{
	global $serverName;
	$returnUrl = $_POST['returnurl'];
	# Don't get tricked into redirecting somewhere
	# unpleasant. You never know. Reject the return URL
	# unless it points to somewhere on our own site.	
	$prefix = "http://$serverName/";
	if (!beginsWith($returnUrl, $prefix)) {
		$returnUrl = "http://$serverName/"; 
	}
	header("Location: $returnUrl");
}

function beginsWith($s, $prefix)
{
	return (substr($s, 0, strlen($prefix)) === $prefix);
}

function sendMail()
{
	# Global variables must be specifically imported in PHP functions
	global $recipient;
	$messages = array();
	$email = $_POST['email'];
	# Allow only reasonable email addresses. Don't let the
	# user trick us into backscattering spam to many people.
	# Make sure the user remembered the @something.com part
	if (!preg_match("/^[\w\+\-\.\~]+\@[\-\w\.\!]+$/", $email)) {
		$messages[] = "That is not a valid email address. Perhaps you left out the @something.com part?";
	}
	$realName = $_POST['realname'];
	if (!preg_match("/^[\w\ \+\-\'\"]+$/", $realName)) {
		$messages[] = "The real name field must contain only alphabetical characters, numbers, spaces, and the + and - signs. We apologize for any inconvenience.";
	}
	$subject = $_POST['subject'];
	# CAREFUL: don't allow hackers to sneak line breaks and additional
	# headers into the message and trick us into spamming for them!
	$subject = preg_replace('/\s+/', ' ', $subject);
	# Make sure the subject isn't blank (apart from whitespace)
	if (preg_match('/^\s*$/', $subject)) {
		$messages[] = "Please specify a subject for your message.";
	}
	
	$body = $_POST['body'];
	# Make sure the message has a body
        if (preg_match('/^\s*$/', $body)) {
		$messages[] = "Your message was blank. Did you mean to say something? Click the Cancel button if you do not wish to send a message.";
	}
	# Check the captcha code if the user is NOT logged in to an account
	if ((!$_SESSION['id']) && function_exists('captchaImgUrl')) {
		if ($_POST['captcha'] != $_SESSION['captchacode']) {
			$messages[] = "You did not enter the security code, or what you entered did not match the code. Please try again.";
		}
	}
	if (count($messages)) {
		# There were errors, so re-display the form with
		# the error messages and let the user correct
		# the problem
		displayForm($messages);
		return;
	}
	# No errors - send the email	
	mail($recipient,
		$subject,
		$body,
		"From: $realName <$email>\r\n" .
		"Reply-To: $realName <$email>\r\n");
	# Thank the user and invite them to continue, at which point
	# we direct them to the page they came from. Don't allow
	# unreasonable characters in the URL
	$escapedReturnUrl = htmlspecialchars($_POST['returnurl']);
?>
<html>
<head>
<title>Thank You</title>
</head>
<body>
<h1>Thank You</h1>
<p>
Thank you for contacting us! Your message has been sent. 
</p>
<form method="POST" action="<?php echo $_SERVER['DOCUMENT_URL']?>">
<input type="submit" name="continue" value="Click Here To Continue"/>
<input 
	type="hidden"
	name="returnurl" 
	value="<?php echo $escapedReturnUrl?>"/>
</form>
</body>
</html>
<?php
}
?>

