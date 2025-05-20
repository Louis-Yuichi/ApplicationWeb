<?php

namespace App\Controllers;

class AuthController extends BaseController
{
	public function login()
	{
		$this->view('login.html.twig');
	}
}
