document.addEventListener('DOMContentLoaded', function()
{
	const btnModifier = document.getElementById('btnModifier');
	const btnGroup    = document.getElementById('btnGroup'   );
	const btnAnnuler  = document.getElementById('btnAnnuler' );
	const btnValider  = document.getElementById('btnValider' );

	btnModifier.addEventListener('click', function()
	{
		btnModifier.classList.add('d-none');
		btnGroup.classList.remove('d-none');
	});

	btnAnnuler.addEventListener('click', function()
	{
		btnGroup.classList.add('d-none');
		btnModifier.classList.remove('d-none');
	});

	btnValider.addEventListener('click', function()
	{
		btnGroup.classList.add('d-none');
		btnModifier.classList.remove('d-none');
	});

	const boutonFlottant = document.getElementById('floatingBtn' );
	const menuFlottant   = document.getElementById('floatingMenu');

	boutonFlottant.addEventListener('click', function()
	{
		menuFlottant.style.display = menuFlottant.style.display === 'block' ? 'none' : 'block';
	});

	document.addEventListener('click', function(evenement)
	{
		if (!boutonFlottant.contains(evenement.target) && !menuFlottant.contains(evenement.target))
		{
			menuFlottant.style.display = 'none';
		}
	});
});

let tabEtudiants = [];

document.getElementById('anneePromotion').addEventListener('change', function()
{
	const annee      = this.value;
	const selectEtu  = document.getElementById('nomEtudiant');
	const champsVide = ['ficheNomPrenom', 'parcours_n2', 'parcours_n1', 'parcours_n', 'apprentissage_but1', 
						'apprentissage_but2', 'apprentissage_but3', 'parcours_but', 'mobilite_etranger', 
						'abs_but1', 'abs_but2', 'abs_but3'];

	selectEtu.innerHTML = '<option value="">Sélectionner un étudiant</option>';

	champsVide.forEach(champ => document.getElementById(champ).textContent = '');
	document.getElementById('nbAvisPromo').textContent = '0';

	tabEtudiants = [];

	if (annee)
	{
		fetch('/api/etudiants/' + annee)
			.then(reponse => reponse.json())
			.then(data =>
			{
				tabEtudiants = data;
				data.forEach(etudiant =>
				{
					const option = document.createElement('option');
					option.value = etudiant.idEtudiant;
					option.textContent = `${etudiant.nomEtudiant} ${etudiant.prenomEtudiant}`;
					selectEtu.appendChild(option);
				});

				document.getElementById('nbAvisPromo').textContent = data.length;
			});
	}
});

document.getElementById('nomEtudiant').addEventListener('change', function()
{
	const id       = this.value;
	const option   = this.selectedOptions[0];
	const etudiant = tabEtudiants.find(e => e.idEtudiant === id);
	
	document.getElementById('ficheNomPrenom').textContent = option && id ? option.textContent : '';

	if (etudiant)
	{
		const parcours = etudiant.parcoursEtudes ? etudiant.parcoursEtudes.replace(/\s+/g, '') : '';
		const mapBut   = { 'S1S2': 'BUT 1', 'S3S4': 'BUT 2', 'S5S6': 'BUT 3' };
		
		const n2 = parcours.slice(-12, -8);
		const n1 = parcours.slice(-8, -4);
		const n  = parcours.slice(-4);

		document.getElementById('parcours_n2'       ).textContent = mapBut[n2] || n2;
		document.getElementById('parcours_n1'       ).textContent = mapBut[n1] || n1;
		document.getElementById('parcours_n'        ).textContent = mapBut[n]  || n;
		document.getElementById('parcours_but'      ).textContent = etudiant.parcoursBUT      || '';
		document.getElementById('mobilite_etranger' ).textContent = etudiant.mobiliteEtranger || '';
	}
	else
	{
		const champsEtudiant = ['parcours_n2', 'parcours_n1', 'parcours_n', 'parcours_but', 'mobilite_etranger'];
		champsEtudiant.forEach(champ => document.getElementById(champ).textContent = '');
	}

	if (id)
	{
		fetch('/api/absences/' + id)
			.then(reponse => reponse.json())
			.then(absences =>
			{
				document.getElementById('abs_but1').textContent = absences.but1;
				document.getElementById('abs_but2').textContent = absences.but2;
				document.getElementById('abs_but3').textContent = absences.but3;
			});
	}
	else
	{
		['abs_but1', 'abs_but2', 'abs_but3'].forEach
		(
			champ => document.getElementById(champ).textContent = ''
		);
	}

	if (id)
	{
		fetch('/api/apprentissage/' + id)
			.then(reponse => reponse.json())
			.then(apprentissage =>
			{
				document.getElementById('apprentissage_but1').textContent = apprentissage.but1;
				document.getElementById('apprentissage_but2').textContent = apprentissage.but2;
				document.getElementById('apprentissage_but3').textContent = apprentissage.but3;
			});
	}
	else
	{
		['apprentissage_but1', 'apprentissage_but2', 'apprentissage_but3'].forEach
		(
			champ => document.getElementById(champ).textContent = ''
		);
	}
});