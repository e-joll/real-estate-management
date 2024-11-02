// public/js/confirm_purchase.js

document.addEventListener("DOMContentLoaded", () => {

    // Manages the "Save and Confirm" action for documents.
    // On click:
    // 1. Prevents default submission.
    // 2. Counts signed documents and updates the display.
    // 3. Submits the form via the modal's confirmation button.
    document.querySelectorAll(".action-saveAndConfirm").forEach((function (element) {
        element.addEventListener("click", (function (event) {
            event.preventDefault()

            let matchingDocumentSignedElements = document.querySelectorAll('option[value="signed"][selected="selected"]')
            let numberDocumentsSigned = matchingDocumentSignedElements.length
            let matchingDocumentElements = document.querySelectorAll('.accordion-item')
            let numberDocuments = matchingDocumentElements.length

            document.querySelector("#number-documents-signed").textContent = numberDocumentsSigned.toString()
            document.querySelector("#total_documents").textContent = numberDocuments.toString()

            document.querySelector("#modal-save-and-confirm-button").addEventListener("click", (function () {
                const form = document.querySelector("#edit-Purchase-form");
                form.requestSubmit(document.querySelector("#modal-save-and-confirm-button"))
            }))
        }))
    }))

    // This script manages the selection of statuses in multiple <select> elements
    // within a form related to documents. It ensures that when a user selects
    // an option from one of the <select> elements, the corresponding option is marked
    // as selected in the DOM.
    const selectDocumentStatusElements = document.querySelectorAll('select[name^="Purchase[documents]"][name$="[status]"]');
    selectDocumentStatusElements.forEach(selectElement => {
        selectElement.addEventListener('change', function() {
            const selectedValue = this.value;

            for (let i = 0; i < this.options.length; i++) {
                const option = this.options[i];
                option.setAttribute('selected', (option.value === selectedValue) ? 'selected' : 'false');
            }
        });
    });
})