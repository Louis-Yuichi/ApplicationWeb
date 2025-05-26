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
		$model = new \App\Models\CandidatModel();
		$data['candidats'] = $model->findAll();
		$data['annees'] = ['2022/2023', '2023/2024', '2024/2025'];
		$data['utilisateur'] = session()->get('utilisateur');

		$this->view('parcoursup/gestion.html.twig', $data);
	}
}
