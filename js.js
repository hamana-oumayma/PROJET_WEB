document.addEventListener("DOMContentLoaded", function () {
    console.log("JS chargé !");

    const modal = document.getElementById("uploadModal");
    const closeBtn = document.querySelector(".close-btn");
    const fileInput = document.getElementById("documentFile");
    const fileUploadArea = document.getElementById("fileUploadArea");
    const fileNameDisplay = document.getElementById("fileName");
    const cancelUploadBtn = document.getElementById("cancelUpload");
    const documentIdInput = document.getElementById("documentId");

    // Fonction pour ouvrir le modal
    function openUploadModal(documentId) {
        console.log("openUploadModal appelé avec l'ID : " + documentId);
        document.getElementById('uploadModal').style.display = 'block';
        document.getElementById('documentId').value = documentId;
    }

    // Boutons de téléversement (documents liés à une candidature ou à compléter)
    const uploadButtons = document.querySelectorAll(".upload-btn");

    // Ajoute un événement au clic pour chaque bouton
    uploadButtons.forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            if (id) {
                openUploadModal(id);
            }
        });
    });

    // Fermer le modal
    closeBtn.addEventListener("click", function () {
        modal.style.display = "none";
        resetForm();
    });

    // Fermer en cliquant à l'extérieur du modal
    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
            resetForm();
        }
    });

    // Bouton Annuler
    cancelUploadBtn.addEventListener("click", function () {
        modal.style.display = "none";
        resetForm();
    });

    // Afficher le nom du fichier
    fileInput.addEventListener("change", function () {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = `Fichier sélectionné : ${fileInput.files[0].name}`;
        } else {
            fileNameDisplay.textContent = "";
        }
    });

    // Activer le clic sur le champ invisible lors du clic dans la zone
    fileUploadArea.addEventListener("click", function () {
        fileInput.click();
    });

    // Drag and drop (optionnel mais stylé)
    fileUploadArea.addEventListener("dragover", function (e) {
        e.preventDefault();
        fileUploadArea.classList.add("dragging");
    });

    fileUploadArea.addEventListener("dragleave", function (e) {
        e.preventDefault();
        fileUploadArea.classList.remove("dragging");
    });

    fileUploadArea.addEventListener("drop", function (e) {
        e.preventDefault();
        fileUploadArea.classList.remove("dragging");

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            fileNameDisplay.textContent = `Fichier sélectionné : ${files[0].name}`;
        }
    });

    // Fonction pour vider le formulaire
    function resetForm() {
        document.getElementById("documentForm").reset();
        fileNameDisplay.textContent = "";
        documentIdInput.value = "";
    }
});
