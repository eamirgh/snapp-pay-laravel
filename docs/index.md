---
layout: home

hero:
  name: "SnappPay Laravel"
  text: "Production-ready SnappPay BNPL Integration"
  tagline: "Resilient, scalable, and fully typed for Laravel 11, 12, and 13 (PHP 8.2+)"
  image:
    src: /logo.svg
    alt: SnappPay Laravel
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/eamirgh/snapp-pay-laravel
    - theme: alt
      text: راهنمای فارسی
      link: /fa/

features:
  - title: Official API 2.1 Conformance
    details: Strictly built against SnappPay Self-CMS Technical Documentation v2.1 including OAuth, Eligibility, Token, Settle, Cancel, and Update.
  - title: Built-in Timeout & Failure Resilience
    details: Fully implements Section 11 (Verify timeout mitigation) and Section 12 (Settle failure mitigation) with automated status checks.
  - title: Strongly Typed DTOs & Validation
    details: First-class CartItem, Cart, and PaymentTokenRequest DTOs enforcing SnappPay transactionId and arithmetic rules before dispatching.
  - title: Multi-Tenant & Multi-Merchant
    details: Seamlessly configure multiple merchant accounts or dynamically construct instances for SaaS and marketplace setups.
  - title: Automatic Token Management
    details: Effortlessly requests OAuth2 tokens with Basic Auth, auto-refreshes on expiration, and offers optional caching.
  - title: Lifecycle Events
    details: Emits typed Laravel events for every major lifecycle stage, allowing easy audit logging and webhook propagation.
---

<div class="tip custom-block" style="padding-top: 8px; margin-top: 2rem;">
  <p class="custom-block-title">QUICK INSTALL</p>
  <pre><code>composer require eamirgh/snapp-pay-laravel</code></pre>
</div>
