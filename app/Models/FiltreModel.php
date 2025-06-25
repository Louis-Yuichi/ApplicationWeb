<?php
// filepath: /home/etudiant/dt231159/STAGE/ApplicationWeb/app/Models/FiltreModel.php

namespace App\Models;

use CodeIgniter\Model;

class FiltreModel extends Model
{
    protected $table = 'Filtre';
    protected $primaryKey = 'idFiltre';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'nomFiltre',
        'typeAction',
        'valeurAction',
        'colonneSource',
        'conditionType',
        'valeurCondition',
        'actif'
    ];

    protected $validationRules = [
        'nomFiltre' => 'required|string|max_length[255]',
        'typeAction' => 'required|in_list[bonus,malus,coefficient,note_directe]',
        'valeurAction' => 'required|numeric',
        'colonneSource' => 'required|string',
        'conditionType' => 'required|in_list[contient,egal,different,commence_par,finit_par,superieur,inferieur,vide,non_vide]',
        'valeurCondition' => 'string',
        'actif' => 'in_list[0,1]'
    ];
}
