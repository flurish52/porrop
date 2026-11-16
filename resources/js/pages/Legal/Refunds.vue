<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue'
import LegalPage from '@/Components/LegalPage.vue'
import { legal } from '@/legal.js'

defineOptions({ layout: LandingLayout })
</script>

<template>
    <LegalPage
        title="Refunds &amp; Returns Policy"
        intro="Every Porrop store belongs to an independent seller, so your first step with any problem is the seller. If that does not work, contact us and we will help where we can."
    >
        <h2>1. Who handles your refund</h2>
        <p>
            You buy from the seller, so the seller is responsible for your order: delivery, the condition of the goods, and any refund or return.
            Porrop is not the seller and does not hold the goods. Porrop can step in to help when a seller does not respond or does not fix a problem (see section 5).
        </p>

        <h2>2. When you can ask for a refund</h2>
        <p>You can ask for a refund or replacement when:</p>
        <ul>
            <li>your order is not delivered or collected, or the seller cannot supply it;</li>
            <li>the item is damaged, faulty or arrives incomplete;</li>
            <li>the item is not what the seller described (wrong item, size, colour, or materially different from the photos);</li>
            <li>you were charged more than once for the same order, or the amount does not match your order.</li>
        </ul>

        <h2>3. Change of mind</h2>
        <p>
            Sellers decide whether they accept returns for a change of mind. Ask the seller before you order if this matters to you.
            If a seller accepts a return, agree with them how the item comes back and who pays for return delivery.
        </p>

        <h2>4. What to do</h2>
        <ul>
            <li><strong>Act quickly.</strong> Tell the seller within <strong>7 days</strong> of delivery or collection (or within 7 days of the promised delivery date if nothing arrived). Earlier is better.</li>
            <li><strong>Contact the seller</strong> using the phone number or WhatsApp link on their store or on your order page. Give your order reference (it starts with <strong>PRP-</strong>) and describe the problem. Photos help.</li>
            <li>Sellers are expected to reply within <strong>2 working days</strong> and to resolve genuine problems fairly.</li>
            <li>Keep your Paystack receipt, your order reference and your messages with the seller.</li>
        </ul>

        <h2>5. If the seller does not help</h2>
        <p>
            Email {{ legal.email }} with your order reference, what happened, and what you have tried. We will review the order record, contact the seller and may ask you for more information.
            We cannot promise a particular outcome, because the goods and the money are with the seller, but we may suspend sellers who ignore valid complaints, and we will do what we reasonably can to help you reach a fair result.
            We cannot help with payments you made outside Porrop checkout.
        </p>

        <h2>6. How refunds are paid</h2>
        <p>
            Approved refunds are paid back to the card or bank account you paid with, through Paystack. Once a refund is started, the time it takes to show in your account depends on Paystack and your bank, and can take several working days.
            Delivery costs you agreed with the seller separately are between you and the seller.
        </p>

        <h2>7. Cancelling before dispatch</h2>
        <p>
            If you want to cancel, contact the seller as soon as possible, before they prepare or send your order. If the seller has not started on it, they should agree to cancel and refund you.
        </p>

        <h2>8. Your rights under the law</h2>
        <p>This policy does not take away any rights you have under Nigerian consumer protection law.</p>

        <h2>9. Contact</h2>
        <p>{{ legal.operator }}, {{ legal.address }}. Email: {{ legal.email }}.</p>
    </LegalPage>
</template>
