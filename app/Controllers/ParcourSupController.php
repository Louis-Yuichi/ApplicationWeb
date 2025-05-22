<?php

namespace App\Controllers;

class ParcourSupController extends BaseController
{
	public function menu()
	{
		$this->view('parcoursup/parcoursup.html.twig');
	}
	public function gestion()
	{
		$this->view('parcoursup/gestion.html.twig');
	}

}
