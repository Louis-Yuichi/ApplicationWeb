document.addEventListener('DOMContentLoaded', function() {
    const normalMode = document.getElementById('normalMode');
    const editMode = document.getElementById('editMode');
    const btnModifier = document.getElementById('btnModifier');
    const btnAnnuler = document.getElementById('btnAnnuler');
    const btnValider = document.getElementById('btnValider');

    btnModifier.addEventListener('click', function() {
        normalMode.style.display = 'none';
        editMode.style.display = 'block';
    });

    btnAnnuler.addEventListener('click', function() {
        editMode.style.display = 'none';
        normalMode.style.display = 'block';
    });

    btnValider.addEventListener('click', function() {
        // Logique de validation ici
        editMode.style.display = 'none';
        normalMode.style.display = 'block';
    });

    document.getElementById('exportPDF').addEventListener('click', function(e) {
        e.preventDefault();
        // Logique d'export PDF
    });

    document.getElementById('exportHTML').addEventListener('click', function(e) {
        e.preventDefault();
        // Logique d'export HTML
    });
});