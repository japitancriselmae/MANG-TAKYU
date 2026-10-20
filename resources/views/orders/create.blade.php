@extends('layouts.app')

@section('title', 'POS — New Order')
@section('page-title', 'Point of Sale')
@section('page-subtitle', 'Choose the service type, tap products to build the cart, then confirm payment.')

@section('content')

<form method="POST" action="{{ route('orders.store') }}" id="orderForm" enctype="multipart/form-data">
    @csrf

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-900">Choose a product to order</h2>
                <p id="menuHint" class="mt-1 text-sm text-slate-500">Choose dine-in or take-out first.</p>
            </div>

            @if ($products->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-4xl">🍗</p>
                    <p class="mt-3 font-semibold text-slate-700">No active products available</p>
                    <p class="mt-1 text-sm text-slate-500">Ask a manager to add products to the menu.</p>
                </div>
            @else
                <div id="productMenu" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $index => $product)
                        <button type="button"
                            class="product-card group relative overflow-hidden rounded-xl border border-slate-200 text-left transition hover:border-[#E31E24] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                            data-product-id="{{ $product->product_id }}"
                            data-product-name="{{ $product->product_name }}"
                            data-price="{{ $product->price }}"
                            data-index="{{ $index }}"
                            aria-label="Add {{ $product->product_name }} to order">
                            @if ($product->image_path)
                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->product_name }}"
                                    class="aspect-[4/3] w-full object-cover" loading="lazy">
                            @else
                                <span class="flex aspect-[4/3] w-full items-center justify-center bg-amber-50 text-6xl" aria-hidden="true">🍗</span>
                            @endif
                            <span class="block p-3">
                                <span class="flex min-h-12 items-start justify-between gap-2">
                                    <span class="font-semibold leading-snug text-slate-800">{{ $product->product_name }}</span>
                                    <span class="whitespace-nowrap font-bold text-[#E31E24]">₱{{ number_format((float) $product->price, 2) }}</span>
                                </span>
                                <span class="mt-3 flex items-center justify-between">
                                    <span class="text-xs text-slate-500">Tap to add</span>
                                    <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-slate-100 px-2 text-xs font-bold text-slate-600">
                                        <span class="product-quantity" id="product-quantity-{{ $index }}">{{ (int) old('items.'.$index.'.quantity', 0) }}</span>
                                    </span>
                                </span>
                            </span>
                        </button>
                        <input type="hidden" class="product-id-input"
                            name="items[{{ $index }}][product_id]" value="{{ $product->product_id }}" disabled>
                        <input type="hidden" class="quantity-hidden-input"
                            name="items[{{ $index }}][quantity]" value="0" disabled>
                    @endforeach
                </div>
            @endif

            <div class="border-t border-slate-100 p-4">
                <h3 class="text-sm font-bold text-slate-800">Suggestive sell</h3>
                <p class="mt-1 text-xs text-slate-500">Offer an add-on or upsize from the available menu. Tap a quick pick to add it.</p>
                <div id="suggestedProducts" class="mt-3 flex flex-wrap gap-2">
                    @foreach ($products->take(4) as $product)
                        <button type="button" data-suggestion-id="{{ $product->product_id }}"
                            class="suggestion-button rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-[#E31E24] hover:text-[#E31E24] disabled:hidden">
                            + {{ $product->product_name }} · ₱{{ number_format((float) $product->price, 2) }}
                        </button>
                    @endforeach
                    @if ($products->isEmpty())
                        <span class="text-xs text-slate-400">No add-ons are available yet.</span>
                    @endif
                </div>
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:sticky xl:top-24">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Read back the order</h2>
                <span id="cartCount" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">0 items</span>
            </div>

            <div class="mt-5">
                <h3 class="font-bold text-slate-900">Ask: Dine-in or take-out?</h3>
                <p class="mt-1 text-sm text-slate-500">Choose the service type before adding items.</p>

                <div class="mt-3 grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="order_type" value="Dine-in" required class="peer sr-only"
                            {{ old('order_type') === 'Dine-in' ? 'checked' : '' }}>
                        <span class="flex min-h-14 items-center justify-center rounded-xl border-2 border-slate-200 px-4 py-3 text-center font-semibold text-slate-700 transition peer-checked:border-[#E31E24] peer-checked:bg-red-50 peer-checked:text-[#E31E24]">
                            🍽️ Dine-in
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="order_type" value="Take-out" required class="peer sr-only"
                            {{ old('order_type') === 'Take-out' ? 'checked' : '' }}>
                        <span class="flex min-h-14 items-center justify-center rounded-xl border-2 border-slate-200 px-4 py-3 text-center font-semibold text-slate-700 transition peer-checked:border-[#E31E24] peer-checked:bg-red-50 peer-checked:text-[#E31E24]">
                            🥡 Take-out
                        </span>
                    </label>
                </div>

            </div>

            <div class="mt-4 rounded-xl bg-slate-50 p-4">
                <div class="flex justify-between gap-3 text-sm">
                    <span class="text-slate-500">Service type</span>
                    <span id="selectedOrderType" class="font-semibold text-slate-800">Not selected</span>
                </div>
                <div id="cartItems" class="mt-3 space-y-3 border-t border-slate-200 pt-3">
                    <p class="text-sm text-slate-500">No products added yet.</p>
                </div>
            </div>

            <div class="mt-4 rounded-xl bg-slate-950 p-4 text-white">
                <div class="text-xs font-semibold uppercase tracking-wider text-white/60">Final purchase amount</div>
                <div id="totalAmount" class="mt-1 text-3xl font-extrabold tabular-nums">₱0.00</div>
            </div>

            <button type="button" id="reviewOrderButton"
                class="mt-5 w-full rounded-xl bg-[#E31E24] py-3.5 font-bold text-white shadow-lg shadow-red-500/20 transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                Review Order & Record Payment
            </button>
            <a href="{{ route('orders.index') }}"
                class="mt-3 block rounded-xl border border-slate-200 py-3 text-center font-semibold text-slate-600 transition hover:bg-slate-50">
                Cancel
            </a>
        </aside>
    </div>

    <div id="orderReviewModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4"
         role="dialog" aria-modal="true" aria-labelledby="orderReviewTitle">
        <section class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5 sm:p-6">
                <div>
                    <h2 id="orderReviewTitle" class="text-xl font-bold text-slate-900">Review Order & Payment</h2>
                    <p class="mt-1 text-sm text-slate-500">Check the items and payment before confirming.</p>
                </div>
                <button type="button" data-close-order-review
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                        aria-label="Close order review">✕</button>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <span class="text-sm font-medium text-slate-600">Service type</span>
                    <span id="reviewOrderType" class="font-bold text-slate-800">Not selected</span>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-bold text-slate-800">Order items</h3>
                    <div id="reviewOrderItems" class="space-y-2"></div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label>
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Payment method</span>
                        <select name="payment_method" id="payment_method" required
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-3 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
                            <option value="">Select method</option>
                            <option value="Cash" {{ old('payment_method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="GCash" {{ old('payment_method') === 'GCash' ? 'selected' : '' }}>GCash</option>
                        </select>
                    </label>
                    <label>
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Amount received</span>
                        <span class="relative block">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-semibold text-slate-500">₱</span>
                            <input type="number" name="amount_paid" id="amount_paid" min="0" step="0.01"
                                   value="{{ old('amount_paid') }}" required placeholder="0.00"
                                   class="w-full rounded-lg border border-slate-200 py-3 pl-8 pr-3 text-lg font-semibold tabular-nums focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
                        </span>
                    </label>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-3">
                    <span class="text-sm font-semibold text-emerald-800">Change</span>
                    <span id="changeAmount" class="text-xl font-extrabold tabular-nums text-emerald-700">₱0.00</span>
                </div>

                <div id="gcashProofField" class="hidden">
                    <label for="gcash_proof" class="mb-2 block text-sm font-semibold text-slate-700">GCash payment screenshot</label>
                    <input type="file" name="gcash_proof" id="gcash_proof" accept="image/jpeg,image/png,image/webp"
                           class="block w-full rounded-lg border border-slate-200 p-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700">
                    <p class="mt-1 text-xs text-slate-500">Required for GCash · JPG, PNG or WebP · up to 5 MB</p>
                    <img id="gcashProofPreview" class="mt-3 hidden max-h-40 rounded-lg border border-slate-200 object-contain" alt="GCash proof preview">
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 p-5 sm:flex-row sm:justify-end sm:p-6">
                <button type="button" data-close-order-review
                        class="rounded-xl border border-slate-200 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                    Back to order
                </button>
                <button type="submit" id="submitOrder"
                        class="rounded-xl bg-[#E31E24] px-5 py-3 font-bold text-white shadow-lg shadow-red-500/20 transition hover:bg-red-700">
                    Confirm Order & Record Payment
                </button>
            </div>
        </section>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('orderForm');
    const productCards = [...document.querySelectorAll('.product-card')];
    const orderTypeInputs = [...document.querySelectorAll('input[name="order_type"]')];
    const methodInput = document.getElementById('payment_method');
    const amountInput = document.getElementById('amount_paid');
    const proofInput = document.getElementById('gcash_proof');
    const proofField = document.getElementById('gcashProofField');
    const proofPreview = document.getElementById('gcashProofPreview');
    const selectedOrderType = document.getElementById('selectedOrderType');
    const cartItems = document.getElementById('cartItems');
    const reviewOrderItems = document.getElementById('reviewOrderItems');
    const reviewOrderType = document.getElementById('reviewOrderType');
    const reviewModal = document.getElementById('orderReviewModal');
    const reviewOrderButton = document.getElementById('reviewOrderButton');
    const totalDisplay = document.getElementById('totalAmount');
    const changeDisplay = document.getElementById('changeAmount');
    const cartCount = document.getElementById('cartCount');
    const quantityFor = (card) => Number(card.querySelector('.product-quantity').textContent) || 0;
    const selectedType = () => orderTypeInputs.find((input) => input.checked)?.value || '';
    const formatMoney = (value) => '₱' + value.toFixed(2);
    const getTotal = () => productCards.reduce((sum, card) => sum + Number(card.dataset.price) * quantityFor(card), 0);

    function setQuantity(card, quantity) {
        card.querySelector('.product-quantity').textContent = Math.max(0, quantity);
        updateCart();
    }

    function updateReviewItems() {
        reviewOrderItems.replaceChildren();
        const selectedCards = productCards.filter((card) => quantityFor(card) > 0);

        if (selectedCards.length === 0) {
            const emptyMessage = document.createElement('p');
            emptyMessage.className = 'rounded-lg bg-slate-50 p-4 text-sm text-slate-500';
            emptyMessage.textContent = 'No products added yet.';
            reviewOrderItems.append(emptyMessage);
        }

        selectedCards.forEach((card) => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3';

            const details = document.createElement('div');
            details.className = 'min-w-0';
            const name = document.createElement('p');
            name.className = 'truncate font-semibold text-slate-800';
            name.textContent = card.dataset.productName;
            const subtotal = document.createElement('p');
            subtotal.className = 'text-sm text-slate-500';
            subtotal.textContent = `${quantityFor(card)} × ${formatMoney(Number(card.dataset.price))} = ${formatMoney(Number(card.dataset.price) * quantityFor(card))}`;
            details.append(name, subtotal);

            const controls = document.createElement('div');
            controls.className = 'flex shrink-0 items-center gap-2';
            [['−', -1], ['+', 1]].forEach(([label, change]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-lg font-bold text-slate-700 hover:border-[#E31E24] hover:text-[#E31E24]';
                button.textContent = label;
                button.setAttribute('aria-label', `${change > 0 ? 'Add' : 'Remove'} one ${card.dataset.productName}`);
                button.addEventListener('click', () => setQuantity(card, quantityFor(card) + change));
                controls.append(button);
            });

            row.append(details, controls);
            reviewOrderItems.append(row);
        });

        reviewOrderType.textContent = selectedType() || 'Not selected';
    }

    function openReviewModal() {
        const hasItems = productCards.some((card) => quantityFor(card) > 0);
        if (!selectedType()) {
            alert('Choose dine-in or take-out before reviewing the order.');
            return;
        }
        if (!hasItems) {
            alert('Add at least one product before reviewing the order.');
            return;
        }

        updateReviewItems();
        reviewModal.classList.remove('hidden');
        reviewModal.classList.add('flex');
        methodInput.focus();
    }

    function closeReviewModal() {
        reviewModal.classList.add('hidden');
        reviewModal.classList.remove('flex');
        reviewOrderButton.focus();
    }

    function updateMenuState() {
        const hasType = Boolean(selectedType());
        productCards.forEach((card) => { card.disabled = !hasType; });
        document.getElementById('menuHint').textContent = hasType
            ? 'Tap a product again to increase its quantity.'
            : 'Choose dine-in or take-out first.';
        selectedOrderType.textContent = selectedType() || 'Not selected';
    }

    function addProduct(productId) {
        const card = productCards.find((item) => item.dataset.productId === String(productId));
        if (!card || !selectedType()) {
            return;
        }

        card.querySelector('.product-quantity').textContent = quantityFor(card) + 1;
        updateCart();
    }

    function updateCart() {
        let total = 0;
        let count = 0;
        cartItems.replaceChildren();

        productCards.forEach((card) => {
            const quantity = quantityFor(card);
            const productIdInput = card.parentElement.querySelector(`input[name="items[${card.dataset.index}][product_id]"]`);
            const quantityInput = card.parentElement.querySelector(`input[name="items[${card.dataset.index}][quantity]"]`);
            productIdInput.disabled = quantity === 0;
            quantityInput.disabled = quantity === 0;
            quantityInput.value = quantity;

            if (quantity === 0) {
                return;
            }

            const subtotal = Number(card.dataset.price) * quantity;
            total += subtotal;
            count += quantity;

            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-2 text-sm';

            const info = document.createElement('div');
            info.className = 'min-w-0';
            const name = document.createElement('p');
            name.className = 'truncate font-semibold text-slate-800';
            name.textContent = card.dataset.productName;
            const line = document.createElement('p');
            line.className = 'text-xs text-slate-500';
            line.textContent = `${quantity} × ${formatMoney(Number(card.dataset.price))} = ${formatMoney(subtotal)}`;
            info.append(name, line);

            const controls = document.createElement('div');
            controls.className = 'flex shrink-0 items-center gap-1';
            [['−', -1], ['+', 1]].forEach(([label, change]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 font-bold text-slate-700 hover:border-[#E31E24] hover:text-[#E31E24]';
                button.textContent = label;
                button.setAttribute('aria-label', `${change > 0 ? 'Add' : 'Remove'} one ${card.dataset.productName}`);
                button.addEventListener('click', () => {
                    card.querySelector('.product-quantity').textContent = Math.max(0, quantityFor(card) + change);
                    updateCart();
                });
                controls.append(button);
            });

            row.append(info, controls);
            cartItems.append(row);
        });

        if (count === 0) {
            const emptyMessage = document.createElement('p');
            emptyMessage.className = 'text-sm text-slate-500';
            emptyMessage.textContent = 'No products added yet.';
            cartItems.append(emptyMessage);
        }

        cartCount.textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
        totalDisplay.textContent = formatMoney(total);

        if (methodInput.value === 'GCash') {
            amountInput.value = total.toFixed(2);
        }

        updateChange(total);
        updateReviewItems();
        document.querySelectorAll('.suggestion-button').forEach((button) => {
            const suggestedCard = productCards.find((card) => card.dataset.productId === button.dataset.suggestionId);
            button.disabled = !selectedType() || (suggestedCard && quantityFor(suggestedCard) > 0);
        });
    }

    function updateChange(total) {
        const change = Number(amountInput.value || 0) - total;
        changeDisplay.textContent = formatMoney(change > 0 ? change : 0);
        changeDisplay.classList.toggle('text-red-600', change < 0);
        changeDisplay.classList.toggle('text-emerald-700', change >= 0);
    }

    function updatePaymentMethod() {
        const isGcash = methodInput.value === 'GCash';
        proofField.classList.toggle('hidden', !isGcash);
        proofInput.required = isGcash;
        amountInput.readOnly = isGcash;

        if (isGcash) {
            amountInput.value = getTotal().toFixed(2);
        }

        updateChange(getTotal());
    }

    productCards.forEach((card) => {
        card.addEventListener('click', () => addProduct(card.dataset.productId));
    });
    reviewOrderButton.addEventListener('click', openReviewModal);
    document.querySelectorAll('[data-close-order-review]').forEach((button) => {
        button.addEventListener('click', closeReviewModal);
    });
    reviewModal.addEventListener('click', (event) => {
        if (event.target === reviewModal) {
            closeReviewModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !reviewModal.classList.contains('hidden')) {
            closeReviewModal();
        }
    });
    orderTypeInputs.forEach((input) => input.addEventListener('change', updateMenuState));
    document.querySelectorAll('.suggestion-button').forEach((button) => {
        button.addEventListener('click', () => addProduct(button.dataset.suggestionId));
    });
    methodInput.addEventListener('change', updatePaymentMethod);
    amountInput.addEventListener('input', () => {
        updateChange(getTotal());
    });
    proofInput.addEventListener('change', () => {
        const file = proofInput.files[0];
        if (!file) {
            proofPreview.classList.add('hidden');
            proofPreview.removeAttribute('src');
            return;
        }

        proofPreview.src = URL.createObjectURL(file);
        proofPreview.classList.remove('hidden');
    });

    form.addEventListener('submit', (event) => {
        if (!productCards.some((card) => quantityFor(card) > 0)) {
            event.preventDefault();
            alert('Add at least one product before confirming the order.');
            return;
        }

        const total = getTotal();
        if (Number(amountInput.value || 0) < total) {
            event.preventDefault();
            alert(`Payment is insufficient. The order total is ${formatMoney(total)}.`);
        }
    });

    updateMenuState();
    updateCart();
    updatePaymentMethod();
})();
</script>
@endpush

@endsection

