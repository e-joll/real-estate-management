// public/js/purchase_autocomplete.js

document.addEventListener('DOMContentLoaded', function () {
    const fields = {
        id: document.getElementById('Purchase_buyer_id'),
        email: document.getElementById('Purchase_buyer_email'),
        firstName: document.getElementById('Purchase_buyer_firstName'),
        lastName: document.getElementById('Purchase_buyer_lastName')
    };

    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.id = 'buyer-suggestions';
    suggestionsContainer.style.zIndex = '1000';
    suggestionsContainer.style.maxHeight = '200px';
    suggestionsContainer.className = 'list-group position-relative overflow-y-scroll';

    const updateBuyerSuggestions = (event) => {
        const queryParams = new URLSearchParams({
            id: fields.id.value,
            email: fields.email.value,
            firstName: fields.firstName.value,
            lastName: fields.lastName.value
        });

        document.querySelector(`#${event.target.id}`).parentElement.appendChild(suggestionsContainer);

        fetch(`api/buyers?${queryParams.toString()}`)
            .then(response => response.json())
            .then(data => {
                suggestionsContainer.innerHTML = '';

                if (data.length > 0) {
                    suggestionsContainer.style.display = 'block'; // Display the container if suggestions exist


                    data.forEach(buyer => {
                        const div = document.createElement('a');
                        div.textContent = `${buyer.firstName} ${buyer.lastName} (${buyer.email})`;
                        div.classList.add('list-group-item', 'list-group-item-action');
                        div.addEventListener('click', () => {
                            fields.id.value = buyer.id;
                            fields.email.value = buyer.email;
                            fields.firstName.value = buyer.firstName;
                            fields.lastName.value = buyer.lastName;
                            suggestionsContainer.textContent = '';
                            suggestionsContainer.style.display = 'none'; // Hide the container after selection
                        });
                        suggestionsContainer.appendChild(div);
                    });
                } else {
                    suggestionsContainer.style.display = 'none'; // Hide if no suggestions
                }
            });
    };

    for (const fieldKey in fields) {
        fields[fieldKey].addEventListener('input', (event) => { updateBuyerSuggestions(event)});
    }

    // Hide suggestions when clicking outside
    document.addEventListener('click', (event) => {
        if (!suggestionsContainer.contains(event.target) && !Object.values(fields).some(field => field === event.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
});