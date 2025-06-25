document.addEventListener('DOMContentLoaded', function()
{
	// Initialiser la recherche
	initSearch();
	
	// Initialiser l'édition des cellules
	initCellEditing();
	
	// Initialiser le stockage des modifications
	window.modifications =
	{};
});

function initSearch()
{
	const searchInput = document.getElementById('searchInput');
	const tableBody   = document.querySelector('tbody');
	const rows        = Array.from(tableBody.querySelectorAll('tr'));

	if (searchInput && tableBody)
	{
		searchInput.addEventListener('input', function()
		{
			const searchValue = this.value.toLowerCase();

			rows.forEach(row => {
				const numCandidat = row.querySelector('td')?.textContent.toLowerCase() || '';
				const nom = row.cells[1]?.textContent.toLowerCase() || '';
				const prenom = row.cells[2]?.textContent.toLowerCase() || '';
				
				if (searchValue === '' || 
					numCandidat.includes(searchValue) || 
					nom.includes(searchValue) || 
					prenom.includes(searchValue))
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
}

function initCellEditing()
{
	const editableCells = document.querySelectorAll('.editable-cell');
	
	editableCells.forEach(cell => {
		cell.addEventListener('click', function()
		{
			if (this.classList.contains('editing')) return;
			
			const originalValue = this.textContent.trim();
			const fieldName = this.dataset.field;
			const candidatId = this.dataset.candidat;
			
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
			
			// Fonction de sauvegarde locale
			const saveValue = () => {
				const newValue = input.value.trim();
				
				if (newValue !== originalValue)
				{
					// Stocker la modification localement
					if (!window.modifications[candidatId])
					{
						window.modifications[candidatId] =
						{};
					}
					window.modifications[candidatId][fieldName] = newValue;
					
					// Marquer la cellule comme modifiée
					this.classList.add('modified');
					
					// Ajouter un indicateur visuel
					if (!this.querySelector('.modification-indicator'))
					{
						const indicator = document.createElement('div');
						indicator.className = 'modification-indicator';
						indicator.title = 'Modifié localement';
						this.appendChild(indicator);
					}
					
					console.log('Modification locale:', candidatId, fieldName, newValue);
				}
				
				this.textContent = newValue;
				this.classList.remove('editing');
				
				// Remettre l'indicateur si nécessaire
				if (window.modifications[candidatId] && window.modifications[candidatId][fieldName])
				{
					const indicator = document.createElement('div');
					indicator.className = 'modification-indicator';
					indicator.title = 'Modifié localement';
					this.appendChild(indicator);
				}
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

// Fonction pour exporter avec les modifications locales
function exportWithModifications()
{
	const exportData = prepareExportData();
	
	// Récupérer les informations depuis les données cachées
	const exportInfo = document.getElementById('exportInfo');
	const codeExaminateur = exportInfo.dataset.code;
	const anneeSelectionnee = exportInfo.dataset.annee;
	
	if (!codeExaminateur || !anneeSelectionnee)
	{
		alert('Erreur: Informations d\'export manquantes');
		return;
	}
	
	// Créer un formulaire caché avec les données modifiées
	const form         = document.createElement('form');
	form.method        = 'POST';
	form.action        = '/exporterEvaluationAvecModifications';
	form.style.display = 'none';
	
	// Ajouter les données d'export
	const dataInput = document.createElement('input');
	dataInput.type  = 'hidden';
	dataInput.name  = 'exportData';
	dataInput.value = JSON.stringify(exportData);
	form.appendChild(dataInput);
	
	// Ajouter les autres données nécessaires
	const codeInput = document.createElement('input');
	codeInput.type  = 'hidden';
	codeInput.name  = 'codeExaminateur';
	codeInput.value = codeExaminateur;
	form.appendChild(codeInput);
	
	const anneeInput = document.createElement('input');
	anneeInput.type  = 'hidden';
	anneeInput.name  = 'annee';
	anneeInput.value = anneeSelectionnee;
	form.appendChild(anneeInput);
	
	// Soumettre le formulaire
	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
}

// Fonction pour préparer les données d'export avec les modifications
function prepareExportData()
{
	const exportData = [];
	const rows = document.querySelectorAll('tbody tr:not(#noResults)');
	
	rows.forEach(row => {
		if (row.style.display === 'none') return; // Ignorer les lignes cachées par la recherche
		
		const cells = row.querySelectorAll('td');
		const candidatData = {};
		
		// Récupérer les données de chaque cellule
		cells.forEach((cell, index) => {
			const fieldName = cell.dataset.field;
			if (fieldName)
			{
				candidatData[fieldName] = cell.textContent.trim();
			}
			else
			{
				// Pour les cellules sans data-field, utiliser l'ordre des colonnes
				const columnNames = ['numCandidat', 'nom', 'prenom', 'groupe', 'codeExaminateur', 'nomExaminateur', 'prenomExaminateur', 'noteDossier', 'commentaire'];
				if (columnNames[index])
				{
					candidatData[columnNames[index]] = cell.textContent.trim();
				}
			}
		});
		
		exportData.push(candidatData);
	});
	
	return exportData;
}
