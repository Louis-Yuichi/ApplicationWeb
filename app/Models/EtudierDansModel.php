<?php

namespace App\Models;

use CodeIgniter\Model;

class EtudierDansModel extends Model
{
    protected $table = 'EtudierDans';
    protected $primaryKey = ['numCandidat', 'idEtablissement'];
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
    
    protected $allowedFields = [
        'numCandidat',
        'idEtablissement',
        'noteLycee',
        'noteFicheAvenir'
    ];

    protected $validationRules = [
        'numCandidat' => 'required',
        'idEtablissement' => 'required',
        'noteLycee' => 'permit_empty|decimal',
        'noteFicheAvenir' => 'permit_empty|decimal'
    ];

    protected $validationMessages = [
        'numCandidat' => [
            'required' => 'Le numéro de candidat est requis'
        ],
        'idEtablissement' => [
            'required' => 'L\'ID de l\'établissement est requis'
        ]
    ];

    // Pour gérer la clé primaire composite
    protected $uniqueKeys = [
        ['numCandidat', 'idEtablissement']
    ];
}
