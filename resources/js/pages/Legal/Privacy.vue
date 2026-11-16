<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue'
import LegalPage from '@/Components/LegalPage.vue'
import { legal } from '@/legal.js'

defineOptions({ layout: LandingLayout })
</script>

<template>
    <LegalPage
        title="Privacy Policy"
        intro="This explains what personal information Porrop collects, why, who sees it, and the choices you have. We follow the Nigeria Data Protection Act 2023 (NDPA)."
    >
        <h2>1. Who is responsible for your data</h2>
        <p>
            {{ legal.operator }} ({{ legal.address }}) operates {{ legal.site }} and is the data controller for the information described here.
            Questions or requests about your data: {{ legal.email }}.
        </p>
        <p>
            When you buy from a store, the seller also receives your order details and uses them to fulfil your order. For that use, the seller is responsible for how they handle the information, and they must follow the law.
        </p>

        <h2>2. What we collect</h2>
        <h3>If you buy something</h3>
        <ul>
            <li>Your name, email address, phone number and delivery address.</li>
            <li>What you ordered, the store, the amount, your order reference and the status of the order.</li>
            <li>Payment status, payment reference and payment channel (for example card or bank transfer). <strong>We never see or store your full card number, PIN or CVV.</strong> Those are entered on Paystack's page.</li>
        </ul>
        <h3>If you are a seller</h3>
        <ul>
            <li>Your name, email, phone number, WhatsApp number and login details (your password is stored only in scrambled form).</li>
            <li>Your store name, description, logo, banner and product photos.</li>
            <li>Your bank name and account number, the account name returned by your bank, and the Paystack sub-account code that goes with it. These are used to pay you.</li>
            <li>Order activity in your store, and your payout-related records.</li>
        </ul>
        <h3>Automatically</h3>
        <ul>
            <li>Technical data such as IP address, browser and device type, pages requested and error logs, used to keep Porrop secure and working.</li>
            <li>A small session cookie that keeps your cart and your login, and a security cookie that protects forms from forgery.</li>
        </ul>
        <p>We do not use advertising cookies or sell personal data.</p>

        <h2>3. Why we use it, and our lawful basis</h2>
        <ul>
            <li><strong>To process your order and pay the seller</strong> (contract): confirming payment, passing the order to the seller, sending receipts and updates.</li>
            <li><strong>To run seller accounts and payouts</strong> (contract): creating your store, setting up your Paystack sub-account, showing your orders.</li>
            <li><strong>To keep Porrop safe</strong> (legitimate interests): preventing fraud, abuse and errors, and fixing problems.</li>
            <li><strong>To meet legal duties</strong> (legal obligation): accounting, tax and responding to lawful requests from authorities.</li>
            <li><strong>To help you</strong> (contract and legitimate interests): answering support and complaint messages.</li>
        </ul>
        <p>We do not send marketing emails to buyers. If that ever changes, we will ask for your consent first, and you will be able to withdraw it at any time.</p>

        <h2>4. Who we share it with</h2>
        <ul>
            <li><strong>The seller</strong> you buy from: your name, phone, email, delivery address and order details.</li>
            <li><strong>Paystack</strong>: processes payments and bank-account checks, and runs its own fraud checks under its own privacy policy.</li>
            <li><strong>Cloudinary</strong>: stores and delivers store and product images.</li>
            <li><strong>Our service providers</strong>: for hosting, email delivery and error monitoring [list your host and email provider here].</li>
            <li><strong>Google Fonts</strong>: our pages load fonts from Google, which means your browser contacts Google when a page opens.</li>
            <li><strong>Authorities</strong> where the law requires it or to protect people from fraud or harm.</li>
        </ul>
        <p>We do not sell your personal data.</p>

        <h2>5. Sending data outside Nigeria</h2>
        <p>
            Some of these providers store or process data in other countries. Where that happens, we only use providers that give your data an appropriate level of protection, and we take the steps the NDPA requires for such transfers.
        </p>

        <h2>6. How long we keep it</h2>
        <p>
            We keep order and payment records for as long as we need them to handle returns, complaints and disputes, to meet accounting and tax rules, and to protect against fraud. Seller account details are kept while the store exists and for the period the law requires afterwards.
            When we no longer need data, we delete it or anonymise it.
        </p>

        <h2>7. Keeping it safe</h2>
        <p>
            We use secure connections (HTTPS), restricted access, hashed passwords and signed, verified payment notifications. No system is perfectly secure.
            If a breach is likely to put people's rights at serious risk, we will notify the Nigeria Data Protection Commission within 72 hours of becoming aware, and tell affected people without delay.
        </p>

        <h2>8. Your rights</h2>
        <p>Under the NDPA you may:</p>
        <ul>
            <li>be told how your data is used (this policy);</li>
            <li>ask for a copy of the data we hold about you;</li>
            <li>ask us to correct data that is wrong or out of date;</li>
            <li>ask us to delete data we no longer need or have no lawful reason to keep;</li>
            <li>ask us to restrict how we use it, or object to certain uses;</li>
            <li>withdraw consent where we rely on it, and ask for your data in a portable format where the rules allow.</li>
        </ul>
        <p>
            To use any of these, email {{ legal.email }} with your name, the email you used and your order reference if you have one. We may need to confirm it is really you.
            We cannot always delete records we must keep by law, but we will tell you if so.
        </p>
        <p>
            If you are not happy with how we handled your data, please tell us first. You also have the right to complain to the Nigeria Data Protection Commission (NDPC).
        </p>

        <h2>9. Children</h2>
        <p>Porrop is for adults. We do not knowingly collect data from anyone under 18. If you think a child has given us data, email us and we will delete it.</p>

        <h2>10. Changes</h2>
        <p>If we change how we use personal data, we will update this page and its effective date, and tell you directly if the change is significant.</p>

        <h2>11. Contact</h2>
        <p>{{ legal.operator }}, {{ legal.address }}. Email: {{ legal.email }}.</p>
    </LegalPage>
</template>
