<?php

class Stuffpress_Emails {
	
	public static function sendWelcomeEmail($email, $username, $password, $token) {
		
		$base = self::getBaseURL();
		$text =   "Welcome $username ! You've just registered at storytlr.\r\n\r\n"
	             ."Before you can login, you must first confirm "
	             ."your registration by clicking this link:\r\n"
				 ."$base/admin/register/validate/key/$token\r\n\r\n"
			     ."If you ever lose or forget your password, a new "
	             ."password will be generated for you and sent to this "
	             ."email address.\r\n\r\n"
	             ."- The storytlr team";
		     
		$mail = new Zend_Mail('utf-8');
		$mail->setBodyText($text);
		$mail->setFrom(self::getFrom(), 'storytlr support');
		$mail->addTo($email, $username);
		$mail->setSubject('Please verify your storytlr account');
		$mail->send();
	}
	
	public static function sendRecoveryEmail($email, $username, $password) {
		$base = self::getBaseURL();
		$text = $username.",\r\n\r\n"
             ."As requested, here is your username and a new password:\r\n\r\n"
             ."Username: ".$username."\r\n"
             ."Password: ".$password."\r\n\r\n"
             ."Would you still have troubles to login into your account, please send us "
			 ." an email to support@storytlr.com\r\n\r\n"
             ."- The storytlr team";
		     
		$mail = new Zend_Mail('utf-8');
		$mail->setBodyText($text);
		$mail->setFrom(self::getFrom(), 'storytlr support');
		$mail->addTo($email, $username);
		$mail->setSubject('Your new password');
		$mail->send();
	}
	
	public static function sendCommentEmail($email, $username, $from_name, $from_email, $comment, $slug) {
		$base = self::getBaseURL();
		$text =	 "$from_name ($from_email) commented on your lifestream:\r\n"
	            ."$comment\r\n\r\n"
	            ."To see the commented item and reply to $from_name, follow this link:\r\n"
	            ."$base/entry/$slug\r\n\r\n"
	            ."Thank you, \r\n\r\n"
	            ."- The storytlr team";
		     
		$mail = new Zend_Mail('utf-8');
		$mail->setBodyText($text);
		$mail->setFrom(self::getFrom(), 'storytlr notification');
		$mail->addTo($email, $username);
		$mail->setSubject("$from_name commented on your lifestream");
		$mail->send();
	}
	
	public static function sendCommentNotifyEmail($email, $username, $from_name, $comment, $source, $item) {
		$base = self::getBaseURL();
		$text =	 "$from_name also commented on this item:\r\n"
			    ."$base/entry/$source/$item/\r\n\r\n"
	            ."-----\r\n$comment\r\n-----\r\n"
	            ."Thank you, \r\n\r\n"
	            ."- The storytlr team";
		     
		$mail = new Zend_Mail('utf-8');
		$mail->setBodyText($text);
		$mail->setFrom(self::getFrom(), 'storytlr notification');
		$mail->addTo($email, $username);
		$mail->setSubject("$from_name commented on an item you commented on");
		$mail->send();
	}
	
	public static function sendRequestEmail($to_email, $from_name, $from_email) {
		$base = self::getBaseURL();
		$text =	 "$from_name ($from_email) requested an invite code for storytlr.\r\n"
	            ."Thank you, \r\n"
	            ."- The storytlr team";
		     
		$mail = new Zend_Mail('utf-8');
		$mail->setBodyText($text);
		$mail->setFrom(self::getFrom(), 'storytlr notification');
		$mail->addTo($to_email, 'storytlr admin');
		$mail->setSubject("$from_name requested an invite code for storytlr");
		$mail->send();
	}
	
	public static function getBaseURL() {
		$host	= "http://" . Zend_Controller_Front::getInstance()->getRequest()->get('SERVER_NAME');
		$base	= Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		return $host.$base;
	}
	
	public static function getFrom() {
		if (Zend_Registry::isRegistered('configuration')) {
			$config = Zend_Registry::get('configuration');
			if (isset($config->app->from_email)) {
				return $config->app->from_email;
			}
		}
		
		return "not-configured@storytlr.org";
	}
}