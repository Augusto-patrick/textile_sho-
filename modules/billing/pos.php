<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'New Bill (POS)';

// ------------------------------------------------------------
// Load all active, in-stock products so JavaScript can search/add
// them to the cart without reloading the page each time.
// We convert the PHP result into a JSON string that gets embedded
// into the page as a JavaScript array — this is how PHP (server)
// and JS (browser) share data.
// ------------------------------------------------------------
$result = $conn->query(
    "SELECT product_id, product_name, sku, selling_price, stock_quantity
     FROM products WHERE status = 'active' ORDER BY product_name"
);
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: 1.3fr 1fr; gap: 20px; max-width: 100%;">

    <!-- LEFT: product search + list -->
    <!-- min-width:0 overrides the browser's default grid-item behavior of
         never shrinking below its content's natural width. Without this,
         a wide table inside can force this column past its 1.3fr share,
         pushing the second column off the visible page entirely. -->
    <div class="card" style="min-width: 0; overflow-x: auto;">
        <h3 style="margin-top:0;">Products</h3>
        <input type="text" id="searchBox" placeholder="Search by name or SKU..." style="margin-bottom:14px;">
        <table>
            <thead><tr><th>Name</th><th>SKU</th><th>Price</th><th>Stock</th><th></th></tr></thead>
            <tbody id="productTableBody"></tbody>
        </table>
    </div>

    <!-- RIGHT: current bill / cart -->
    <div class="card" style="min-width: 0;">
        <h3 style="margin-top:0;">Current Bill</h3>

        <div class="form-group">
            <label>Customer (optional — leave blank for walk-in)</label>
            <input type="text" id="walkinName" placeholder="Walk-in customer name">
        </div>
        <div class="form-group">
            <label>Phone (optional)</label>
            <input type="text" id="walkinPhone">
        </div>

        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Total</th><th></th></tr></thead>
            <tbody id="cartTableBody">
                <tr id="emptyCartRow"><td colspan="4" style="text-align:center; color:var(--text-muted);">No items added yet</td></tr>
            </tbody>
        </table>

        <div style="text-align:right; margin-top:14px; font-size:1.1rem;">
            <strong>Total: ₹<span id="cartTotal">0.00</span></strong>
        </div>

        <div class="form-group" style="margin-top:14px;">
            <label>Payment Method</label>
            <select id="paymentMethod">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="upi">UPI</option>
            </select>
        </div>

        <button class="btn" style="width:100%;" onclick="submitBill()">Complete Sale</button>
    </div>
</div>

<!-- 
  This form is invisible to the user — JavaScript fills in the hidden
  field with the cart data (as JSON text) and submits it to process_bill.php
  when "Complete Sale" is clicked. This is how we send the whole cart
  to PHP in one go, instead of one request per item.
-->
<form id="billForm" method="POST" action="process_bill.php" style="display:none;">
    <input type="hidden" name="cart_json" id="cart_json">
    <input type="hidden" name="walkin_name" id="hidden_walkin_name">
    <input type="hidden" name="walkin_phone" id="hidden_walkin_phone">
    <input type="hidden" name="payment_method" id="hidden_payment_method">
</form>

<script>
// The product list from PHP, now available as a JS array of objects.
const products = <?= json_encode($products) ?>;

// The cart is just a JS object: { productId: {name, price, qty, stock}, ... }
let cart = {};

function renderProducts(filter = '') {
    const tbody = document.getElementById('productTableBody');
    tbody.innerHTML = '';
    const filtered = products.filter(p =>
        p.product_name.toLowerCase().includes(filter.toLowerCase()) ||
        (p.sku || '').toLowerCase().includes(filter.toLowerCase())
    );
    filtered.forEach(p => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${p.product_name}</td>
            <td>${p.sku || '-'}</td>
            <td>₹${parseFloat(p.selling_price).toFixed(2)}</td>
            <td>${p.stock_quantity}</td>
            <td><button class="btn" style="padding:5px 12px;" onclick="addToCart(${p.product_id})" ${p.stock_quantity <= 0 ? 'disabled' : ''}>Add</button></td>
        `;
        tbody.appendChild(row);
    });
}

function addToCart(productId) {
    // Number(...) on both sides avoids a silent false-negative when one
    // side is a string ("2", from MySQL via PHP) and the other is a
    // genuine number (2, from the onclick attribute) — === would treat
    // those as different values even though they represent the same id.
    const product = products.find(p => Number(p.product_id) === Number(productId));
    if (!product) return;

    if (!cart[productId]) {
        cart[productId] = { name: product.product_name, price: parseFloat(product.selling_price), qty: 0, stock: product.stock_quantity };
    }
    if (cart[productId].qty >= product.stock_quantity) {
        alert('Cannot add more — only ' + product.stock_quantity + ' in stock.');
        return;
    }
    cart[productId].qty += 1;
    renderCart();
}

function removeFromCart(productId) {
    delete cart[productId];
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cartTableBody');
    const ids = Object.keys(cart);
    let total = 0;

    if (ids.length === 0) {
        tbody.innerHTML = '<tr id="emptyCartRow"><td colspan="4" style="text-align:center; color:var(--text-muted);">No items added yet</td></tr>';
    } else {
        tbody.innerHTML = '';
        ids.forEach(id => {
            const item = cart[id];
            const lineTotal = item.price * item.qty;
            total += lineTotal;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td>${item.qty}</td>
                <td>₹${lineTotal.toFixed(2)}</td>
                <td><a href="#" onclick="removeFromCart(${id}); return false;" style="color:var(--danger);">✕</a></td>
            `;
            tbody.appendChild(row);
        });
    }
    document.getElementById('cartTotal').textContent = total.toFixed(2);
}

function submitBill() {
    if (Object.keys(cart).length === 0) {
        alert('Add at least one product to the bill.');
        return;
    }
    document.getElementById('cart_json').value = JSON.stringify(cart);
    document.getElementById('hidden_walkin_name').value = document.getElementById('walkinName').value;
    document.getElementById('hidden_walkin_phone').value = document.getElementById('walkinPhone').value;
    document.getElementById('hidden_payment_method').value = document.getElementById('paymentMethod').value;
    document.getElementById('billForm').submit();
}

document.getElementById('searchBox').addEventListener('input', (e) => renderProducts(e.target.value));

renderProducts();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
