document.addEventListener('DOMContentLoaded', function()
{
	const searchInput = document.getElementById('searchInput');
	const anneeSelect = document.querySelector('select[name="annee"]');
	
	// Auto-redirection vers l'année actuelle si aucune année n'est sélectionnée
	if (anneeSelect && !anneeSelect.value && !window.location.search.includes('annee='))
	{
		const anneeActuelle = new Date().getFullYear();
		const anneeDefaut = anneeActuelle + '/' + (anneeActuelle + 1);
		window.location.href = '/gestionParcourSup?annee=' + encodeURIComponent(anneeDefaut);
		return;
	}
	
	// Gérer le changement d'année
	if (anneeSelect)
	{
		anneeSelect.addEventListener('change', function()
		{
			updateCalculerButtonText();
			if (this.value)
			{
				window.location.href = '/gestionParcourSup?annee=' + encodeURIComponent(this.value);
			}
		});
	}
	
	// Fonction de recherche
	const tableBody = document.querySelector('tbody');
	if (tableBody && searchInput)
	{
		const rows = Array.from(tableBody.querySelectorAll('tr'));
		
		searchInput.addEventListener('input', function()
		{
			const searchValue = this.value.toLowerCase();
			
			rows.forEach(row =>
			{
				const numCandidat = row.querySelector('td')?.textContent.toLowerCase() || '';
				
				if (searchValue === '' || numCandidat.includes(searchValue))
				{
					row.style.display = '';
				}
				else
				{
					row.style.display = 'none';
				}
			});

			const visibleRows = rows.filter(row => row.style.display !== 'none');
			if (visibleRows.length === 0 && searchValue !== '')
			{
				if (!document.getElementById('noResults'))
				{
					const noResults = document.createElement('tr');
					noResults.id = 'noResults';
					noResults.innerHTML = `<td colspan="${rows[0]?.cells.length || 1}" class="text-center">Aucun candidat trouvé</td>`;
					tableBody.appendChild(noResults);
				}
			}
			else
			{
				const noResults = document.getElementById('noResults');
				if (noResults) noResults.remove();
			}
		});
	}
	
	// Gestion de l'édition des cellules
	initCellEditing();
	
	// Mettre à jour le texte du bouton avec l'année
	updateCalculerButtonText();
});

function initCellEditing()
{
	const editableCells = document.querySelectorAll('.editable-cell');
	
	editableCells.forEach(cell =>
	{
		cell.addEventListener('click', function() {
			if (this.classList.contains('editing')) return;
			
			const originalValue = this.textContent.trim();
			const fieldName = this.dataset.field;
			const candidatId = this.dataset.candidat;
			const annee = this.dataset.annee;
			
			// Créer l'input d'édition
			const input = document.createElement('input');
			input.type = 'text';
			input.value = originalValue;
			input.className = 'editable-input';
			
			// Remplacer le contenu
			this.innerHTML = '';
			this.appendChild(input);
			this.classList.add('editing');
			
			// Focus et sélection
			input.focus();
			input.select();
			
			// Fonction de sauvegarde
			const saveValue = () => {
				const newValue = input.value.trim();
				
				if (newValue !== originalValue)
				{
					// Afficher l'indicateur de sauvegarde
					const indicator = document.createElement('div');
					indicator.className = 'save-indicator';
					this.appendChild(indicator);
					
					// Sauvegarder via AJAX
					fetch('/modifierCandidat',
					{
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						},
						body: JSON.stringify({
							numCandidat: candidatId,
							anneeUniversitaire: annee,
							field: fieldName,
							value: newValue
						})
					})
					.then(response => response.json())
					.then(data =>
					{
						if (data.success)
						{
							indicator.classList.add('show');
							setTimeout(() =>
							{
								if (indicator.parentNode)
								{
									indicator.remove();
								}
							}, 2000);
						}
						else
						{
							alert('Erreur lors de la sauvegarde: ' + (data.message || 'Erreur inconnue'));
							this.textContent = originalValue; // Revenir à l'ancienne valeur
						}
					})
					.catch(error =>
					{
						console.error('Erreur:', error);
						alert('Erreur de connexion');
						this.textContent = originalValue;
					});
					
					this.textContent = newValue;
				}
				else
				{
					this.textContent = originalValue;
				}
				
				this.classList.remove('editing');
			};
			
			// Événements pour sauvegarder
			input.addEventListener('blur', saveValue);
			input.addEventListener('keydown', function(e)
			{
				if (e.key === 'Enter')
				{
					saveValue();
				}
				else if (e.key === 'Escape')
				{
					cell.textContent = originalValue;
					cell.classList.remove('editing');
				}
			});
		});
	});
}

function updateNotesInTable(notes, notesGlobales)
{
	// Parcourir toutes les lignes visibles du tableau
	const rows = document.querySelectorAll('tbody tr:not(#noResults)');
	let notesUpdated = 0;
	
	rows.forEach(row => {
		const cells = row.querySelectorAll('td');
		if (cells.length > 0)
		{
			// Trouver le numCandidat (première cellule)
			const numCandidat = cells[0].textContent.trim();
			
			// Si on a les notes pour ce candidat
			if (notes[numCandidat] !== undefined)
			{
				// Mettre à jour la note dossier
				const noteDossierCell = row.querySelector('[data-field="noteDossier"]');
				if (noteDossierCell)
				{
					noteDossierCell.style.backgroundColor = '#d4edda';
					noteDossierCell.style.transition = 'background-color 0.5s ease';
					noteDossierCell.textContent = notes[numCandidat];
					notesUpdated++;
					
					// Remettre la couleur normale après 3 secondes
					setTimeout(() =>
					{
						noteDossierCell.style.backgroundColor = '';
					}, 3000);
				}
				
				// Mettre à jour la note globale
				const noteGlobaleCell = row.querySelector('[data-field="noteGlobale"]');
				if (noteGlobaleCell && notesGlobales && notesGlobales[numCandidat] !== undefined)
				{
					noteGlobaleCell.style.backgroundColor = '#cce5ff';
					noteGlobaleCell.style.transition = 'background-color 0.5s ease';
					noteGlobaleCell.textContent = notesGlobales[numCandidat];
					
					// Remettre la couleur normale après 3 secondes
					setTimeout(() =>
					{
						noteGlobaleCell.style.backgroundColor = '';
					}, 3000);
				}
			}
		}
	});
	
	console.log(`${notesUpdated} notes mises à jour dans l'interface`);
}

function calculerNotesAjax()
{
	const btn               = document.getElementById('calculerNotesBtn');
	const spinner           = document.getElementById('loadingSpinner');
	const messageArea       = document.getElementById('messageArea');
	const anneeSelectionnee = getSelectedAnnee();
	
	// Vérifier qu'une année est sélectionnée
	if (!anneeSelectionnee)
	{
		showMessage('error', 'Veuillez sélectionner une année avant de calculer les notes');
		return;
	}
	
	// Désactiver le bouton et afficher le spinner
	btn.disabled = true;
	spinner.classList.remove('d-none');
	
	// Effacer les anciens messages
	messageArea.innerHTML = '';
	
	fetch('/calculerNotesAjax',
	{
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-Requested-With': 'XMLHttpRequest'
		},
		body: JSON.stringify({
			annee: anneeSelectionnee
		})
	})
	.then(response => response.json())
	.then(data =>
	{
		if (data.success)
		{
			// Afficher un message de succès avec l'année
			showMessage('success', `${data.nbMisAJour} candidat(s) de l'année ${anneeSelectionnee} mis à jour avec succès (notes dossier + globales recalculées)`);
			
			// Mettre à jour les notes dans le tableau
			updateNotesInTable(data.notes, data.notesGlobales);
		}
		else
		{
			showMessage('error', data.message || 'Erreur lors du calcul des notes');
		}
	})
	.catch(error =>
	{
		console.error('Erreur:', error);
		showMessage('error', 'Erreur de connexion lors du calcul des notes');
	})
	.finally(() =>
	{
		// Réactiver le bouton et cacher le spinner
		btn.disabled = false;
		spinner.classList.add('d-none');
	});
}

function getSelectedAnnee()
{
	const anneeSelect = document.querySelector('select[name="annee"]');
	return anneeSelect ? anneeSelect.value : '';
}

function showMessage(type, message)
{
	const messageArea = document.getElementById('messageArea');
	const alertClass  = type === 'success' ? 'alert-success' : 'alert-danger';
	const icon        = type === 'success' ? 'check-circle'  : 'exclamation-triangle';
	
	messageArea.innerHTML = `
		<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
			<i class="fas fa-${icon}"></i> ${message}
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	`;
	
	// Auto-masquer après 5 secondes
	setTimeout(() =>
	{
		const alert = messageArea.querySelector('.alert');
		if (alert)
		{
			alert.remove();
		}
	}, 5000);
}

function updateCalculerButtonText()
{
	const annee     = getSelectedAnnee();
	const btnText   = document.getElementById('btnText');
	const anneeInfo = document.getElementById('anneeInfo');
	
	if (btnText && anneeInfo)
	{
		if (annee)
		{
			btnText.textContent = 'Calculer les résultats ';
			anneeInfo.textContent = `pour ${annee}`;
			anneeInfo.classList.remove('d-none');
		}
		else
	{
			btnText.textContent = 'Calculer les résultats';
			anneeInfo.classList.add('d-none');
		}
	}
}
