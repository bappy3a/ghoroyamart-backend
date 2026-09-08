<?php

namespace Database\Seeders;

use App\Models\CustomPage;
use Illuminate\Database\Seeder;

class CustomPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'name' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'sub_title' => 'How we collect, use, and protect your personal information.',
                'en_content' => <<<'HTML'
<h2>Introduction</h2>
<p>Ghoroya Mart ("we", "us", or "our") respects your privacy and is committed to protecting the personal information you share with us when you use our website, mobile app, and related services.</p>
<p>This Privacy Policy explains what information we collect, how we use it, and the choices you have. By using our services, you agree to the practices described here.</p>

<h2>Information We Collect</h2>
<p>We may collect the following types of information:</p>
<ul>
    <li><strong>Account details</strong> — name, phone number, email address, and password when you register or place an order.</li>
    <li><strong>Order and delivery information</strong> — shipping address, payment method details (processed by our payment partners), and order history.</li>
    <li><strong>Device and usage data</strong> — IP address, browser type, device identifiers, pages visited, and approximate location used to improve performance and security.</li>
    <li><strong>Communications</strong> — messages you send to our support team, reviews, and feedback.</li>
</ul>

<h2>How We Use Your Information</h2>
<p>We use your information to:</p>
<ul>
    <li>Process orders, payments, and deliveries.</li>
    <li>Create and manage your account.</li>
    <li>Provide customer support and respond to inquiries.</li>
    <li>Send order updates, service notices, and (with your consent) promotional offers.</li>
    <li>Improve our website, products, and shopping experience.</li>
    <li>Detect fraud, enforce our terms, and comply with legal obligations.</li>
</ul>

<h2>Sharing of Information</h2>
<p>We do not sell your personal information. We may share limited data with:</p>
<ul>
    <li>Delivery partners and logistics providers to fulfill orders.</li>
    <li>Payment processors to complete transactions securely.</li>
    <li>Service providers who help us operate hosting, analytics, or customer support tools.</li>
    <li>Authorities when required by law or to protect our rights and users.</li>
</ul>

<h2>Cookies and Similar Technologies</h2>
<p>We use cookies and similar technologies to keep you signed in, remember preferences, and understand how our store is used. You can control cookies through your browser settings; disabling some cookies may affect site functionality.</p>

<h2>Data Security and Retention</h2>
<p>We take reasonable technical and organizational measures to protect your information. No method of transmission over the internet is completely secure, so we cannot guarantee absolute security.</p>
<p>We retain personal data only as long as needed for the purposes described in this policy, or as required by applicable law.</p>

<h2>Your Rights</h2>
<p>Depending on applicable law, you may request access to, correction of, or deletion of your personal information, or ask us to limit certain processing. Contact us using the details below to make a request.</p>

<h2>Children's Privacy</h2>
<p>Our services are not directed to children under 13. We do not knowingly collect personal information from children. If you believe a child has provided us data, please contact us so we can delete it.</p>

<h2>Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. The revised version will be posted on this page with an updated effective date. Continued use of our services after changes means you accept the updated policy.</p>

<h2>Contact Us</h2>
<p>If you have questions about this Privacy Policy or how we handle your data, please contact Ghoroya Mart through the support channels listed on our website.</p>
HTML,
            ],
            [
                'name' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'sub_title' => 'The rules and conditions for using Ghoroya Mart.',
                'en_content' => <<<'HTML'
<h2>Agreement to Terms</h2>
<p>Welcome to Ghoroya Mart. By accessing or using our website, mobile app, or services, you agree to these Terms of Service. If you do not agree, please do not use our services.</p>

<h2>Eligibility and Accounts</h2>
<p>You must provide accurate information when creating an account or placing an order. You are responsible for keeping your login credentials secure and for all activity under your account. Notify us promptly if you suspect unauthorized access.</p>

<h2>Products and Pricing</h2>
<p>We strive to display product descriptions, images, and prices accurately. Errors may occur. We reserve the right to correct pricing or availability mistakes and to cancel orders affected by such errors. Prices and offers may change without prior notice unless an order has already been confirmed.</p>

<h2>Orders and Payment</h2>
<p>Placing an order constitutes an offer to purchase. We may accept or decline an order for reasons including stock limits, suspected fraud, or payment issues. Payment must be completed through the methods we support. You agree to provide valid payment and billing information.</p>

<h2>Shipping and Delivery</h2>
<p>Delivery times are estimates and may vary by location, product availability, and courier conditions. Risk of loss passes to you upon delivery to the address you provided, unless otherwise required by law. Please ensure your contact and address details are correct.</p>

<h2>User Conduct</h2>
<p>You agree not to misuse our services, including by attempting unauthorized access, interfering with site operation, submitting false information, or using the platform for unlawful purposes. We may suspend or terminate access for violations of these terms.</p>

<h2>Intellectual Property</h2>
<p>All content on Ghoroya Mart—including logos, text, graphics, product images, and software—is owned by us or our licensors and is protected by intellectual property laws. You may not copy, modify, or distribute our content without prior written permission.</p>

<h2>Disclaimer and Limitation of Liability</h2>
<p>Our services are provided on an "as is" and "as available" basis. To the fullest extent permitted by law, Ghoroya Mart is not liable for indirect, incidental, or consequential damages arising from your use of the services or any products purchased through them.</p>

<h2>Indemnification</h2>
<p>You agree to indemnify and hold harmless Ghoroya Mart and its team from claims arising out of your misuse of the services or violation of these terms.</p>

<h2>Governing Law</h2>
<p>These terms are governed by the laws of Bangladesh, without regard to conflict-of-law principles. Disputes shall be resolved in the competent courts of Bangladesh, unless applicable consumer law requires otherwise.</p>

<h2>Changes to These Terms</h2>
<p>We may update these Terms of Service at any time. Continued use of our services after changes are posted constitutes acceptance of the revised terms.</p>

<h2>Contact</h2>
<p>For questions about these Terms of Service, please contact Ghoroya Mart through the support channels on our website.</p>
HTML,
            ],
            [
                'name' => 'Refund Policy',
                'slug' => 'refund-policy',
                'sub_title' => 'When and how you can request returns and refunds.',
                'en_content' => <<<'HTML'
<h2>Overview</h2>
<p>At Ghoroya Mart, we want you to be satisfied with your purchase. This Refund Policy explains when returns and refunds are available and how to request them.</p>

<h2>Eligibility for Returns</h2>
<p>You may request a return or refund if:</p>
<ul>
    <li>The product you received is damaged, defective, or not as described.</li>
    <li>You received the wrong item.</li>
    <li>The product is unused, in original packaging, and returned within the stated return window (where returns are allowed for that product category).</li>
</ul>
<p>Some items may be non-returnable for hygiene or perishable reasons (for example, opened personal-care products, certain food items, or custom-made goods). Non-returnable products will be noted on the product page when applicable.</p>

<h2>Return Window</h2>
<p>Unless otherwise stated, eligible returns must be requested within <strong>7 days</strong> of delivery. Requests submitted after this period may be declined.</p>

<h2>How to Request a Return or Refund</h2>
<ol>
    <li>Contact our support team with your order number, product details, and a brief description of the issue.</li>
    <li>Include clear photos if the item is damaged, defective, or incorrect.</li>
    <li>Wait for our confirmation and return instructions before sending the product back (if a physical return is required).</li>
</ol>

<h2>Inspection and Approval</h2>
<p>Returned items are inspected to confirm they meet our return conditions. We may decline a return if the product shows signs of use beyond inspection, is missing parts or packaging, or was returned outside the allowed window.</p>

<h2>Refund Method and Timing</h2>
<p>Once a return is approved:</p>
<ul>
    <li>Refunds are issued to the original payment method whenever possible.</li>
    <li>Cash-on-delivery orders may be refunded via mobile banking or another method we arrange with you.</li>
    <li>Processing typically takes <strong>3–10 business days</strong> after we receive and approve the returned item, depending on your bank or payment provider.</li>
</ul>

<h2>Exchanges</h2>
<p>If you prefer an exchange for the same or a different product, contact support. Exchanges depend on stock availability and may require an additional payment or partial refund if prices differ.</p>

<h2>Shipping Costs</h2>
<p>If the return is due to our error (wrong, damaged, or defective item), we cover reasonable return shipping costs. For other eligible returns, return shipping may be deducted from your refund or paid by you, as communicated during the return process.</p>

<h2>Canceled Orders</h2>
<p>If you cancel an order before it is shipped, any payment collected will be refunded according to the same timing guidelines above. Once an order is out for delivery, cancellation may not be possible; you may instead follow the return process after delivery where eligible.</p>

<h2>Contact</h2>
<p>For return or refund help, contact Ghoroya Mart support with your order number. We will guide you through the next steps.</p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            CustomPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }

        $this->command?->info('Custom pages seeded successfully!');
    }
}
