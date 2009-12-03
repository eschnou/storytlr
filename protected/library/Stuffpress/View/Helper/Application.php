<?php

class Stuffpress_View_Helper_Application
{
	/**
	 * Returns site base url
	 *
	 * $file is appended to the base url for simplicity
	 *
	 * @param string $file
	 * @return string
	 */
	public function application()
	{
		return $this;
	}
	
	public function username()
	{
		$application = Stuffpress_Application::getInstance();
		return $application->user->username;
	}
	
	public function authenticated() {
		$application = Stuffpress_Application::getInstance();
		return ($application->role == 'guest') ? false : true;
	}
	
	public function hasRole($role)
	{
		$application = Stuffpress_Application::getInstance();
		// TODO this is temporary as it will only work in our 
		// current simple scenario
		return ($role == $application->role);		
	}
}