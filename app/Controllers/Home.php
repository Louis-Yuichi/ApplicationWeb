<?php

namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{
		$this->view('account/login.html.twig');
	}

	public function register()
	{
		$this->view('account/register.html.twig');
	}

	public function accueil()
	{
		$this->view('accueil.html.twig');
	}
}