<?php

namespace App\Models;

use CodeIgniter\Model;

class EtablissementModel extends Model
{
    protected $table = 'Etablissement';
    protected $primaryKey = 'idEtablissement';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'nomEtablissement',
        'villeEtablissement',
        'codePostalEtablissement',
        'departementEtablissement',
        'paysEtablissement'
    ];

    public function firstOrCreate(array $data)
    {
        // Chercher d'abord l'établissement
        $etablissement = $this->where('nomEtablissement', $data['nomEtablissement'])
                             ->where('villeEtablissement', $data['villeEtablissement'])
                             ->first();
        
        // Si non trouvé, créer
        if (!$etablissement) {
            $this->insert($data);
            $etablissement = $this->where('nomEtablissement', $data['nomEtablissement'])
                                ->where('villeEtablissement', $data['villeEtablissement'])
                                ->first();
        }
        
        return $etablissement;
    }
}
