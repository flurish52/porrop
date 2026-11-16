<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue'
import LegalPage from '@/Components/LegalPage.vue'
import { legal } from '@/legal.js'

defineOptions({ layout: LandingLayout })
</script>

<template>
    <LegalPage
        title="Terms of Use"
        intro="Porrop is a marketplace platform. You buy from independent sellers, not from Porrop. Please read how that works before you order."
    >
        <h2>1. Who we are</h2>
        <p>
            Porrop ({{ legal.site }}) is operated by {{ legal.operator }} ("Porrop", "we", "us"), {{ legal.address }}.
            You can reach us at {{ legal.email }}. By visiting or buying through any Porrop page you agree to these terms.
            If you do not agree, please do not use Porrop.
        </p>

        <h2>2. What Porrop is, and what it is not</h2>
        <p>
            Porrop lets independent sellers open online stores and take payments. Each store, product, price and description belongs to the seller who posted it.
        </p>
        <ul>
            <li><strong>The seller is the one selling to you.</strong> The sale contract is between you and the seller. Porrop is not the seller, does not own, hold or inspect the goods, and is not a party to that contract.</li>
            <li>Porrop provides the store pages, the cart and checkout, and the payment connection (through Paystack).</li>
            <li>We do not guarantee the quality, safety, legality or availability of any item, or that a seller will deliver.</li>
        </ul>

        <h2>3. Who can use Porrop</h2>
        <p>You must be at least 18 and able to enter a binding contract under Nigerian law. If you order for someone else, you confirm you are allowed to do so.</p>

        <h2>4. Placing an order</h2>
        <ul>
            <li>You can check out as a guest. You must give a correct name, email address, phone number and delivery address. We and the seller use these to confirm and fulfil your order.</li>
            <li>All prices are in Nigerian naira (NGN). The price you see at checkout is the price you pay through Porrop.</li>
            <li>An order is only confirmed after your payment succeeds and you see your order reference (it starts with <strong>PRP-</strong>). Until then nothing is reserved for you.</li>
            <li>Stock changes quickly. If an item sells out while you pay, the seller will contact you to arrange a refund or a replacement.</li>
            <li>We or the seller may cancel an order we reasonably believe is fraudulent, mispriced by obvious error, or impossible to fulfil. If you already paid, you will be refunded.</li>
        </ul>

        <h2>5. Payment</h2>
        <p>
            Payments are processed by Paystack. Your card or bank details are entered on Paystack's secure page. They are not seen or stored by Porrop.
            Paystack's own terms apply to the payment itself. Keep your order reference and Paystack receipt as proof of payment.
        </p>

        <h2>6. Delivery and pickup</h2>
        <p>
            Delivery or pickup is arranged between you and the seller. Any delivery cost is agreed with the seller directly and is not charged through Porrop checkout.
            Give the seller accurate details and be reachable on the phone number you provided. Delivery times are the seller's estimates, not promises from Porrop.
        </p>

        <h2>7. Refunds, returns and problems with an order</h2>
        <p>
            See our <a href="/refunds">Refunds &amp; Returns Policy</a>. In short: contact the seller first, and contact us if the seller does not respond or resolve it.
        </p>

        <h2>8. Talking to sellers</h2>
        <p>
            Store pages may show a link to chat with the seller on WhatsApp. That conversation is between you and the seller. Porrop does not see it and is not responsible for it.
            For your safety, pay for orders through Porrop checkout so your payment and order are on record. We cannot help with payments made outside Porrop.
        </p>

        <h2>9. Acceptable use</h2>
        <p>You agree not to:</p>
        <ul>
            <li>use Porrop for anything unlawful, or to defraud anyone;</li>
            <li>give false details or use someone else's payment method without permission;</li>
            <li>attempt to break, overload, scrape or gain unauthorised access to Porrop;</li>
            <li>upload malicious code, or harass sellers or other users.</li>
        </ul>

        <h2>10. Content and intellectual property</h2>
        <p>
            The Porrop name, logo and website design belong to us. Product photos, store names and descriptions belong to the sellers who posted them.
            You may not copy or reuse them without permission, except for normal personal browsing and sharing a store link.
        </p>

        <h2>11. Availability</h2>
        <p>We work to keep Porrop running but cannot promise it will always be available or error-free. We may change, pause or stop parts of it at any time.</p>

        <h2>12. Our responsibility to you</h2>
        <p>
            Nothing in these terms removes any right you have under Nigerian consumer protection law that cannot be excluded.
            Subject to that, and because sellers, not Porrop, supply the goods: Porrop is not liable for goods that are late, missing, faulty or not as described, or for what a seller does or fails to do.
            To the extent the law allows, our total liability to you for any claim connected with Porrop is limited to the amount you paid for the order the claim relates to.
            We are not liable for indirect or consequential loss.
        </p>

        <h2>13. Suspending access</h2>
        <p>We may restrict or block access to Porrop for anyone who breaks these terms, harms others, or puts the platform at risk.</p>

        <h2>14. Privacy</h2>
        <p>How we handle your personal data is explained in our <a href="/privacy">Privacy Policy</a>.</p>

        <h2>15. Changes to these terms</h2>
        <p>We may update these terms. The version on this page, with its effective date, is the one that applies. Orders you place after a change are covered by the new version.</p>

        <h2>16. Governing law and disputes</h2>
        <p>
            These terms are governed by the laws of the Federal Republic of Nigeria. Please contact us first at {{ legal.email }} so we can try to resolve any complaint informally.
            If that fails, the courts of {{ legal.courts }} will have jurisdiction.
        </p>

        <h2>17. Contact</h2>
        <p>{{ legal.operator }}, {{ legal.address }}. Email: {{ legal.email }}.</p>
    </LegalPage>
</template>
