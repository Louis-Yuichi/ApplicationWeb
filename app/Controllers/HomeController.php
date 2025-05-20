<?php

namespace App\Controllers;

class HomeController extends BaseController
{
	public function index()
	{
		$this->view('login.html.twig');
	}
}
