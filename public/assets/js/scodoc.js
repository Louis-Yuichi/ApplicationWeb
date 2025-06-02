document.addEventListener('DOMContentLoaded', function()
{
	const btnModifier = document.getElementById('btnModifier');
	const btnGroup = document.getElementById('btnGroup');
	const btnAnnuler = document.getElementById('btnAnnuler');
	const btnValider = document.getElementById('btnValider');

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
	const floatingBtn = document.getElementById('floatingBtn');
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