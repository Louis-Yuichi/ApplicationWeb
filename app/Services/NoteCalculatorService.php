<?php
// filepath: /home/etudiant/dt231159/STAGE/ApplicationWeb/app/Services/NoteCalculatorService.php

namespace App\Services;

class NoteCalculatorService
{
    private $coeffLycee = 2;
    private $coeffFicheAvenir = 1;
    private $coeffDossier = 8;

    /**
     * Calcule la note globale avec les coefficients
     */
    public function calculerNoteGlobale($noteLycee, $noteFicheAvenir, $noteDossier)
    {
        // Valeurs par défaut si notes manquantes
        $noteLycee = $noteLycee ?? 0;
        $noteFicheAvenir = $noteFicheAvenir ?? 0;
        $noteDossier = $noteDossier ?? 0;
        
        // Calcul de la moyenne pondérée
        $totalPoints = ( $noteLycee       * $this->coeffLycee       ) + 
                       ( $noteFicheAvenir * $this->coeffFicheAvenir ) + 
                       ( $noteDossier     * $this->coeffDossier     );
        $totalCoeff = $this->coeffLycee + $this->coeffFicheAvenir + $this->coeffDossier;
        
        $noteGlobale = $totalPoints / $totalCoeff;
        
        // S'assurer que la note reste dans les limites (0-20)
        return max(0, min(20, $noteGlobale));
    }

    /**
     * Applique les filtres pour calculer la note dossier
     */
    public function appliquerFiltres($candidat, $filtres)
    {
        // Utiliser noteLycee comme note de base, sinon 10
        $noteBase = 10;
        
        if (isset($candidat['noteLycee']) && !empty($candidat['noteLycee']) && is_numeric($candidat['noteLycee']))
        {
            $noteBase = floatval($candidat['noteLycee']);
        }
        
        $noteFinale = $noteBase;
        
        foreach ($filtres as $filtre)
        {
            if ($this->evaluerCondition($candidat, $filtre))
            {
                switch ($filtre['typeAction'])
                {
                    case 'bonus':
                        $noteFinale += $filtre['valeurAction'];
                        break;
                    case 'malus':
                        $noteFinale -= abs($filtre['valeurAction']);
                        break;
                    case 'coefficient':
                        $noteFinale *= $filtre['valeurAction'];
                        break;
                    case 'note_directe':
                        $noteFinale = $filtre['valeurAction'];
                        break;
                }
            }
        }
        
        // S'assurer que la note reste dans les limites (0-20)
        return max(0, min(20, $noteFinale));
    }

    /**
     * Évalue si une condition de filtre est remplie
     */
    private function evaluerCondition($candidat, $filtre)
    {
        $valeurCandidat = $candidat[$filtre['colonneSource']] ?? '';
        $valeurCondition = $filtre['valeurCondition'];
        
        switch ($filtre['conditionType'])
        {
            case 'contient':
                return stripos($valeurCandidat, $valeurCondition) !== false;
            case 'egal':
                return strcasecmp($valeurCandidat, $valeurCondition) === 0;
            case 'different':
                return strcasecmp($valeurCandidat, $valeurCondition) !== 0;
            case 'commence_par':
                return stripos($valeurCandidat, $valeurCondition) === 0;
            case 'finit_par':
                return strlen($valeurCondition) <= strlen($valeurCandidat) && 
                    strcasecmp(substr($valeurCandidat, -strlen($valeurCondition)), $valeurCondition) === 0;
            default:
                return false;
        }
    }
}