<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Stripe Test</title>
  <script src="https://js.stripe.com/v3/"></script>
  <style>
    /* Basic page styling */
   body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #1F2937; /* Dark blue-gray from the image background */
    color: #e0e0e0; /* Light gray for text */
}
.container { 
    max-width: 800px; 
    margin: auto; 
    padding: 20px; 
    background: #374758; /* Slightly lighter dark blue-gray for containers */
    border-radius: 8px; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.3); /* Darker shadow for contrast */
}
h1, h2 { 
    color: #ffffff; /* White for headings */
}
form { 
    margin-bottom: 20px; 
}
label { 
    display: block; 
    margin: 8px 0 4px; 
    color: #cccccc; /* Lighter gray for labels */
}
input { 
    width: 100%; 
    padding: 8px; 
    margin-bottom: 10px; 
    border-radius: 4px; 
    border: 1px solid #5a6b7d; /* Border color for inputs */
    background: #2a3644; /* Dark background for inputs */
    color: #ffffff; /* White text in inputs */
}
button { 
    padding: 10px 15px; 
    background: #8BC34A; /* Green from the search button in the image */
    color: #ffffff; /* White text on buttons */
    border: none; 
    border-radius: 6px; 
    cursor: pointer; 
}
button:hover { 
    background: #7CB342; /* Slightly darker green on hover */
}
pre { 
    background: #2a3644; /* Dark background for preformatted text */
    padding: 10px; 
    border-radius: 6px; 
    overflow-x: auto; 
    color: #ffffff; /* White text for preformatted content */
}
.card-section { 
    display: flex; 
    flex-direction: column; 
    gap: 12px; 
    max-width: 500px; 
    margin-bottom: 20px; 
}

#save-card{
   margin: 8px 0 4px; 
}

#form-pay{
   margin: 8px 0 4px; 
}

#card-element { 
    padding: 10px; 
    border: 1px solid #5a6b7d; /* Border color for card element */
    border-radius: 6px; 
    background: #2a3644; /* Dark background for card element */
    color: #ffffff; /* White text in card element */
}
#error-message { 
    color: #ff7f7f; /* Soft red for error messages */
    font-size: 14px; 
}
#cards-list ul { 
    list-style: none; 
    padding: 0; 
}
#cards-list li { 
    padding: 10px; 
    border-bottom: 1px solid #4a5b6c; /* Slightly lighter dark border for list items */
    color: #cccccc; /* Light gray for list item text */
}

select {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  background-color: #fff;
  font-size: 14px;
  color: #333;
  appearance: none; /* Oculta el estilo nativo */
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='gray' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  cursor: pointer;
}

select:focus {
  border-color: #635bff;
  outline: none;
  box-shadow: 0 0 4px rgba(99, 91, 255, 0.6);
}


  .logo-container {
    display: flex;
    flex-direction: row;  
    justify-content: space-between;

    align-items: start;  
  }

  

  
  </style>
</head>


<div class="container">
  <div class="logo-container">
  <h1>Stripe Courtwin Demo</h1>
  <img src="/logo.svg" alt="Logo" />
  </div>
  <!-- Crear Cliente -->
  <h2>Create Stripe Customer</h2>
  <form id="createCustomerForm">
    <label for="name">Name:</label>
    <input type="text" id="name" required>
    <label for="email">Email:</label>
    <input type="email" id="email" required>
    <button type="submit">Create Customer</button>
  </form>
  <pre id="resultado"></pre>

  <!-- Asignar ID Cliente -->
  <h2>Assign Stripe Customer ID</h2>
  <form id="stripeForm">
    <label for="idStripeCustomer">Customer ID:</label>
    <input type="text" id="idStripeCustomer" required>
    <button type="submit">Accept</button>
  </form>
  <p id="resultado2"></p>

  <!-- Guardar Tarjeta -->
  <h2>Save Cards</h2>
  <div>
    <div id="card-element"></div>
    <button id="save-card">Save Card</button>
    <div id="error-message"></div>
  </div>

  <!-- Listar y Eliminar Tarjetas -->
  <h2>Associated Cards</h2>
  <div id="cards-list"></div>

  <!-- Realizar Pago -->
  <h2>Make Payment</h2>
  <form id="chargeForm">
    <label for="amount">Amount (USD):</label>
    <input type="number" id="amount" value="5" required>
    <label for="selectCard">Select Card:</label>
    <select id="selectCard"></select>
    <button type="submit" id="form-pay">Pay</button>
  </form>
  <pre id="chargeResult"></pre>
</div>

<script>
  let idStripeCustomer = "";

  // Crear Cliente
  const form = document.getElementById("createCustomerForm");
  const resultado = document.getElementById("resultado");
  const resultado2 = document.getElementById("resultado2");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();

    try {
      const res = await fetch("http://127.0.0.1:8000/api/v1/stripe/create-customer", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name, email }),
      });
      console.log(res);
      const data = await res.json();
      resultado.textContent = JSON.stringify(data, null, 2);
      idStripeCustomer = data.id;
      resultado2.textContent = `Captured ID: ${idStripeCustomer}`;
      loadAndShowCards();
    } catch (err) {
      console.error(err);
      resultado.textContent = "Error creating customer.";
    }
  });

  // Asignar ID manual
  const form2 = document.getElementById("stripeForm");
  form2.addEventListener("submit", (e) => {
    e.preventDefault();
    idStripeCustomer = document.getElementById("idStripeCustomer").value.trim();
    resultado2.textContent = `Captured ID: ${idStripeCustomer}`;
    loadAndShowCards();
  });

  // Stripe Setup
  const stripe = Stripe("{{ config('services.stripe.key') }}");
  const elements = stripe.elements();
  const card = elements.create("card");
  card.mount("#card-element");

  // Guardar tarjeta
  document.getElementById("save-card").addEventListener("click", async () => {
    if (!idStripeCustomer) return alert("No Stripe customer ID");

    try {
      const res = await fetch("http://127.0.0.1:8000/api/v1/stripe/setup-intent", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ customer_id: idStripeCustomer }),
      });
      const data = await res.json();
      if (data.error) return document.getElementById("error-message").textContent = data.error;

      const result = await stripe.confirmCardSetup(data.client_secret, {
        payment_method: { card, billing_details: { name: "Example Name", email: "client@example.com" } },
      });

      if (result.error) {
        document.getElementById("error-message").textContent = result.error.message;
      } else {
        alert("Card saved! ID: " + result.setupIntent.payment_method);
        loadAndShowCards();
      }
    } catch (err) {
      console.error(err);
      document.getElementById("error-message").textContent = "Request error";
    }
  });

  // Obtener tarjetas
  async function fetchCards(customerId) {
    const url = new URL("http://127.0.0.1:8000/api/v1/stripe/cards");
    url.searchParams.set('customer_id', customerId);
    const res = await fetch(url.toString(), { method: 'GET', headers: { 'Content-Type': 'application/json' } });
    return await res.json();
  }

  // Renderizar y eliminar tarjetas
  function renderCards(cards) {
    const container = document.getElementById('cards-list');
    const select = document.getElementById('selectCard');
    container.innerHTML = '';
    select.innerHTML = '';

    if (!cards || cards.length === 0) {
      container.textContent = 'No cards';
      return;
    }

    const ul = document.createElement('ul');

    cards.forEach(card => {
      const li = document.createElement('li');
      li.innerHTML = `
        <strong>${card.brand}</strong> • **** **** **** ${card.last4}<br>
        Exp: ${card.exp_month}/${card.exp_year}<br>
        Holder: ${card.billing_name}<br>
        ID: ${card.id}
        <button data-id="${card.id}" class="delete-card">Delete</button>
      `;
      ul.appendChild(li);

      // Agregar al select para pagos
      const option = document.createElement('option');
      option.value = card.id;
      option.textContent = `${card.brand} ****${card.last4}`;
      select.appendChild(option);
    });

    container.appendChild(ul);

    // Eliminar tarjeta
    document.querySelectorAll('.delete-card').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        if (!confirm('Delete this card?')) return;
        try {
          const res = await fetch(`http://127.0.0.1:8000/api/v1/stripe/cards/${id}`, { method: 'DELETE' });
          const data = await res.json();
          alert(data.message || 'Card deleted');
          loadAndShowCards();
        } catch (err) {
          console.error(err);
          alert('Error deleting card');
        }
      });
    });
  }

  async function loadAndShowCards() {
    if (!idStripeCustomer) return;
    try {
      const res = await fetchCards(idStripeCustomer);
      renderCards(res.cards || []);
    } catch {
      document.getElementById('cards-list').textContent = 'Error fetching cards';
    }
  }

  // Pagar con tarjeta
  const chargeForm = document.getElementById('chargeForm');
  chargeForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const paymentMethodId = document.getElementById('selectCard').value;
    const amount = parseFloat(document.getElementById('amount').value) * 100;

    if (!idStripeCustomer || !paymentMethodId) return alert('Missing customer or card');

    try {
      const res = await fetch('/api/v1/stripe/charge', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          customer_id: idStripeCustomer,
          payment_method_id: paymentMethodId,
          amount: amount,
          currency: 'usd'
        })
      });
      console.log(res);
      const data = await res.json();
      if (!res.ok) return alert('Charge error: ' + (data.message || res.statusText));

      if (data.status === 'succeeded') alert('Payment succeeded!');
      else if (data.status === 'requires_action') {
        const result = await stripe.confirmCardPayment(data.client_secret);
        if (result.error) alert('Payment authentication failed: ' + result.error.message);
        else if (result.paymentIntent.status === 'succeeded') alert('Payment succeeded after auth!');
      } else {
        alert('Payment status: ' + data.status);
      }
    } catch (err) {
      console.error(err);
      alert('Unexpected error: ' + err.message);
    }
  });

</script>

</body>
</html>