<?php

namespace App\Controllers;

class ScodocController extends BaseController
{
	public function menu()
	{
		$this->view('scodoc/scodoc.html.twig');
	}
}