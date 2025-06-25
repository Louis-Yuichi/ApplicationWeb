<?php

namespace App\Models;

use CodeIgniter\Model;

class EtudierDansModel extends Model
{
    protected $table = 'EtudierDans';
    protected $primaryKey = ['numCandidat', 'idEtablissement', 'anneeUniversitaire'];
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'numCandidat',
        'idEtablissement', 
        'anneeUniversitaire',
        'noteLycee',
        'noteFicheAvenir'
    ];

    protected $useTimestamps = false;
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
}
