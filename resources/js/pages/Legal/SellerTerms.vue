<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue'
import LegalPage from '@/Components/LegalPage.vue'
import { legal } from '@/legal.js'

defineOptions({ layout: LandingLayout })

// Passed from the route so these figures always match what CommissionCalculator really charges.
const props = defineProps({
    commission: { type: Object, default: () => ({ rate: 10, min: 100, max: 5000 }) },
})

const naira = (n) => `₦${Number(n).toLocaleString('en-NG')}`
</script>

<template>
    <LegalPage
        title="Seller Terms"
        intro="These terms are for anyone who opens a store on Porrop. They cover fees, how you get paid, and what we expect from you. By opening a store you agree to them."
    >
        <h2>1. Who these terms are between</h2>
        <p>
            They are between you (the seller, personally or for your business) and {{ legal.operator }} ("Porrop", "we"), {{ legal.address }}.
            They apply together with our <a href="/terms">Terms of Use</a>, <a href="/privacy">Privacy Policy</a> and <a href="/refunds">Refunds &amp; Returns Policy</a>.
        </p>

        <h2>2. Opening and running a store</h2>
        <ul>
            <li>You must be at least 18 and able to sell lawfully in Nigeria. If you sell for a business, you confirm you can bind it.</li>
            <li>Give true information when you register and keep it up to date (name, email, phone, WhatsApp number, bank account).</li>
            <li>You are responsible for everything done through your account. Keep your password private. Tell us at once if you think someone else has access.</li>
            <li>Your store address is <strong>{{ legal.site }}/store/your-name</strong>. You may not pick a name that impersonates another person, brand or Porrop itself. We may change or remove a name that does.</li>
            <li>Depending on our settings, a new store may go live straight away or only after we approve it. We may decline, pause or remove any store at our discretion if these terms are breached or we have good reason to worry about it.</li>
        </ul>

        <h2>3. Your listings</h2>
        <ul>
            <li>Describe items honestly and accurately: what it is, its condition, sizes and options, and the true price in naira.</li>
            <li>Only upload photos and text you own or have the right to use.</li>
            <li>Keep stock numbers accurate. Do not sell what you do not have.</li>
            <li>Do not list illegal, stolen, counterfeit or unsafe goods. That includes weapons, illegal drugs, controlled or prescription medicines you are not licensed to sell, adult content, and anything that infringes someone's rights.</li>
            <li>We may remove a listing or store that breaks these rules, without notice where the risk is serious.</li>
        </ul>

        <h2>4. Fees</h2>
        <p>
            Opening a store is free. There is no monthly or listing fee at present. Porrop earns a platform fee on each paid order:
        </p>
        <ul>
            <li><strong>{{ commission.rate }}%</strong> of the order's items subtotal,</li>
            <li>with a minimum of <strong>{{ naira(commission.min) }}</strong> and a maximum of <strong>{{ naira(commission.max) }}</strong> per order,</li>
            <li>and never more than the items subtotal itself.</li>
        </ul>
        <p>
            For example, with the rates above, a {{ naira(20000) }} order costs you {{ naira(Math.min(Math.max(20000 * commission.rate / 100, commission.min), commission.max)) }}, and you keep the rest.
            The fee is worked out on the items subtotal at the time of payment and is shown on each order in your dashboard.
        </p>
        <p>
            At present Porrop covers Paystack's payment processing charge out of its own fee, so it is not deducted from your share. We may change our fees, or that arrangement, by giving you reasonable notice on the website or by email. A change never applies to orders already paid.
            You are responsible for any taxes that apply to your sales and income.
        </p>

        <h2>5. How you get paid</h2>
        <ul>
            <li>Payments are processed by Paystack. When you save your bank account, Porrop sets up a Paystack sub-account for you. You do <strong>not</strong> need your own Paystack account.</li>
            <li>When a customer pays, Paystack automatically splits the payment: our platform fee goes to Porrop and the rest is settled directly to your bank account on Paystack's settlement schedule. <strong>Porrop does not hold your sales money.</strong></li>
            <li>Settlement timing is controlled by Paystack and your bank. Porrop is not responsible for delays on their side.</li>
            <li>You must give a bank account that belongs to you or your business. We show you the account name the bank returns, and you must confirm it is yours before it is saved. If you confirm a wrong account, money may be paid to the wrong person and may not be recoverable. Neither Porrop nor Paystack is liable for that.</li>
            <li>Until your bank account is saved, customers cannot pay at your store.</li>
        </ul>

        <h2>6. Fulfilling orders</h2>
        <ul>
            <li>When an order is paid you will be notified. Contact the customer, prepare the order and keep its status updated in your dashboard (processing, ready, completed).</li>
            <li>Deliver or hand over what was ordered, as described, within the time you promised or a reasonable time.</li>
            <li>Delivery costs, if any, are agreed between you and the customer. They are not collected through Porrop checkout.</li>
            <li>If you cannot fulfil an order, tell the customer straight away and refund them.</li>
        </ul>

        <h2>7. Refunds, returns and chargebacks</h2>
        <p>
            You are responsible to your customers for the goods you sell. You must follow our <a href="/refunds">Refunds &amp; Returns Policy</a>, which includes refunding customers for orders you cannot deliver, that arrive damaged, or that are not as described.
            Because Paystack pays you directly, Porrop cannot hold back or reverse a settlement. You agree to fund any refund, payment reversal or chargeback that arises from your sales, and to cooperate with us and Paystack when one is raised.
            Whether we refund the platform fee on a refunded order is at our discretion.
        </p>

        <h2>8. Keep payments on Porrop</h2>
        <p>
            Orders that start in your Porrop store must be paid through Porrop checkout. Do not ask customers to pay you separately, off the platform, for the same order to avoid the platform fee.
            If we find this happening, we may suspend your store and recover the fees that were avoided.
        </p>

        <h2>9. Customer information</h2>
        <p>
            You will receive customers' names, phone numbers, emails and delivery addresses so you can fulfil their orders. Use this information only to fulfil the order and handle related questions or complaints. Do not sell it, share it or use it for marketing without consent, and keep it secure.
            You must follow the Nigeria Data Protection Act 2023 for the information you receive. See our <a href="/privacy">Privacy Policy</a>.
        </p>

        <h2>10. Your content and our licence</h2>
        <p>
            You keep ownership of your logo, photos and descriptions. You give Porrop a non-exclusive, worldwide, royalty-free licence to host, display and promote them for as long as your store is on Porrop, so we can run your store and share its link. Images are stored with our image host (Cloudinary).
            You confirm you have the right to give us that licence.
        </p>

        <h2>11. Suspension and closing your store</h2>
        <ul>
            <li>You may stop selling at any time. Fulfil the orders already paid first.</li>
            <li>We may suspend or close a store for breach of these terms, fraud, repeated complaints, unlawful goods, or risk to customers or the platform. We will tell you why where we can.</li>
            <li>Terms that by their nature should survive closing (fees on paid orders, refund duties, liability, data duties) continue to apply.</li>
        </ul>

        <h2>12. Our responsibility</h2>
        <p>
            Porrop provides the platform "as is". We do not promise a minimum number of sales or that the service will never be interrupted.
            To the extent the law allows, we are not liable for lost profits, lost sales or indirect loss, and our total liability to you for any claim is limited to the platform fees you paid us in the three months before the claim.
            Nothing here excludes liability that cannot lawfully be excluded.
        </p>

        <h2>13. You protect us from your sales</h2>
        <p>
            You agree to compensate Porrop for loss, claims and reasonable costs that arise from the goods you sell, your content, or your breach of these terms or the law.
        </p>

        <h2>14. Changes, law and disputes</h2>
        <p>
            We may update these terms and will give reasonable notice of important changes. If you keep selling after the effective date you accept the new version.
            Nigerian law governs these terms. Please contact us first at {{ legal.email }} to settle any dispute informally; if that fails, the courts of {{ legal.courts }} have jurisdiction.
        </p>

        <h2>15. Contact</h2>
        <p>{{ legal.operator }}, {{ legal.address }}. Email: {{ legal.email }}.</p>
    </LegalPage>
</template>
