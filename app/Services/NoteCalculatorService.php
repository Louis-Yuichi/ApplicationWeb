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
	public function evaluerCondition($candidat, $filtre)
	{
		$colonneSource = $filtre['colonneSource'];
		$valeurCondition = $filtre['valeurCondition'];
		
		// Récupérer la valeur du candidat (peut venir de différentes tables grâce aux jointures)
		$valeurCandidat = $candidat[$colonneSource] ?? '';
		
		// Convertir en string pour la comparaison
		$valeurCandidat = strval($valeurCandidat);
		$valeurCondition = strval($valeurCondition);
		
		switch ($filtre['conditionType'])
		{
			case 'contient':
				// Ne pas chercher dans les valeurs "vides"
				if ($this->isEmptyValue($valeurCandidat))
				{
					return false;
				}
				return stripos($valeurCandidat, $valeurCondition) !== false;
				
			case 'egal':
				return strcasecmp($valeurCandidat, $valeurCondition) === 0;
				
			case 'different':
				return strcasecmp($valeurCandidat, $valeurCondition) !== 0;
				
			case 'commence_par':
				// Ne pas chercher dans les valeurs "vides"
				if ($this->isEmptyValue($valeurCandidat))
				{
					return false;
				}
				return stripos($valeurCandidat, $valeurCondition) === 0;
				
			case 'finit_par':
				// Ne pas chercher dans les valeurs "vides"
				if ($this->isEmptyValue($valeurCandidat))
				{
					return false;
				}
				return strlen($valeurCondition) <= strlen($valeurCandidat) && 
					strcasecmp(substr($valeurCandidat, -strlen($valeurCondition)), $valeurCondition) === 0;
					
			case 'superieur':
				// Ignorer les valeurs non numériques et les "vides"
				if ($this->isEmptyValue($valeurCandidat) || !is_numeric($valeurCandidat) || !is_numeric($valeurCondition))
				{
					return false;
				}
				return floatval($valeurCandidat) > floatval($valeurCondition);
				
			case 'inferieur':
				// Ignorer les valeurs non numériques et les "vides"
				if ($this->isEmptyValue($valeurCandidat) || !is_numeric($valeurCandidat) || !is_numeric($valeurCondition))
				{
					return false;
				}
				return floatval($valeurCandidat) < floatval($valeurCondition);
				
			case 'vide':
				// CONSIDÉRER COMME VIDE : '', null, '-', ou whitespace seulement
				return $this->isEmptyValue($valeurCandidat);
				
			case 'non_vide':
				// CONSIDÉRER COMME NON-VIDE : tout sauf '', null, '-', ou whitespace seulement
				return !$this->isEmptyValue($valeurCandidat);
				
			default:
				return false;
		}
	}

	/**
	 * Vérifie si une valeur est considérée comme "vide" dans le système
	 * Vide = null, chaîne vide, tiret seul, ou seulement des espaces
	 */
	private function isEmptyValue($value)
	{
		// Null ou vraiment vide
		if (empty($value))
		{
			return true;
		}
		
		// Convertir en string et nettoyer
		$value = trim(strval($value));
		
		// Chaîne vide après nettoyage
		if ($value === '')
		{
			return true;
		}
		
		// Le tiret par défaut du système
		if ($value === '-')
		{
			return true;
		}
		
		// Variantes possibles du "vide"
		$emptyVariants = [
			'N/A', 'n/a', 'NA', 'na',
			'NULL', 'null',
			'VIDE', 'vide',
			'-', '--', '---',
			'Non renseigné', 'non renseigné'
		];
		
		foreach ($emptyVariants as $variant)
		{
			if (strcasecmp($value, $variant) === 0)
			{
				return true;
			}
		}
		
		return false;
	}
}
