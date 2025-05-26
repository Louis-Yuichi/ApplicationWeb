<?php
namespace App\Models;
use CodeIgniter\Model;

class CandidatModel extends Model
{
    protected $table = 'candidat';
    protected $primaryKey = 'numCandidat';
    protected $allowedFields = ['numCandidat', 'nomCandidat', 'noteDossier', 'commentaire'];
}
