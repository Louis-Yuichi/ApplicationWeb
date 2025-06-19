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
		// Action de validation
		btnGroup.classList.add('d-none');
		btnModifier.classList.remove('d-none');
	});

	// Gestion du menu flottant
	const floatingBtn  = document.getElementById('floatingBtn' );
	const floatingMenu = document.getElementById('floatingMenu');

	floatingBtn.addEventListener('click', function()
	{
		floatingMenu.style.display = floatingMenu.style.display === 'block' ? 'none' : 'block';
	});

	// Fermer le menu si on clique ailleurs
	document.addEventListener('click', function(event)
	{
		if (!floatingBtn.contains(event.target) && !floatingMenu.contains(event.target))
		{
			floatingMenu.style.display = 'none';
		}
	});
});

let etudiants = [];

document.getElementById('anneePromotion').addEventListener('change', function()
{
	const annee = this.value;
	const selectEtu = document.getElementById('nomEtudiant');

	selectEtu.innerHTML = '<option value="">Sélectionner un étudiant</option>';

	document.getElementById('ficheNomPrenom').textContent = '';
	document.getElementById('parcours_n2'   ).textContent = '';
	document.getElementById('parcours_n1'   ).textContent = '';
	document.getElementById('parcours_n'    ).textContent = '';

	etudiants = [];
	// Ajout : reset le nombre d'avis
	document.getElementById('nbAvisPromo').textContent = '0';

	if (annee)
	{
		fetch('/api/etudiants/' + annee)
			.then(r => r.json())
			.then(data =>
			{
				etudiants = data;
				data.forEach(etu =>
				{
					const opt = document.createElement('option');
					opt.value = etu.idEtudiant;
					opt.textContent = etu.nomEtudiant + ' ' + etu.prenomEtudiant;
					selectEtu.appendChild(opt);
				});
				// Ajout : affiche le nombre d'étudiants S6
				document.getElementById('nbAvisPromo').textContent = data.length;
			});
	}
});

document.getElementById('nomEtudiant').addEventListener('change', function()
{
	const id  = this.value;
	const opt = this.selectedOptions[0];
	document.getElementById('ficheNomPrenom').textContent = opt && id ? opt.textContent : '';

	const etu = etudiants.find(e => e.idEtudiant === id);
	let parcours = etu && etu.parcoursEtudes ? etu.parcoursEtudes.replace(/\s+/g, '') : '';

	// Remplacement S1S2/S3S4/S5S6 par BUT 1/2/3
	let n2 = parcours.slice(-12, -8);
	let n1 = parcours.slice(-8, -4);
	let n  = parcours.slice(-4);

	const mapBut = { 'S1S2': 'BUT 1', 'S3S4': 'BUT 2', 'S5S6': 'BUT 3' };

	document.getElementById('parcours_n2').textContent = mapBut[n2] || n2;
	document.getElementById('parcours_n1').textContent = mapBut[n1] || n1;
	document.getElementById('parcours_n').textContent  = mapBut[n]  || n;

	// Récupère et affiche les absences
	if (id)
	{
		fetch('/api/absences/' + id)
			.then(r => r.json())
			.then(abs =>
			{
				document.getElementById('abs_but1').textContent = abs.but1;
				document.getElementById('abs_but2').textContent = abs.but2;
				document.getElementById('abs_but3').textContent = abs.but3;
			});
	}
	else
	{
		document.getElementById('abs_but1').textContent = '';
		document.getElementById('abs_but2').textContent = '';
		document.getElementById('abs_but3').textContent = '';
	}
});