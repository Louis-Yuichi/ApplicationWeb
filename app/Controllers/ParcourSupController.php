<?php

namespace App\Controllers;

class ParcourSupController extends BaseController
{
	public function menu()
	{
		$this->view('parcoursup/parcoursup.html.twig');
	}
}
