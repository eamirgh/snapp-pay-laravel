import { defineConfig } from 'vitepress'

const base = process.env.VITEPRESS_BASE || '/snapp-pay-laravel/'

export default defineConfig({
  base,
  title: 'SnappPay Laravel',
  description: 'Production-ready SnappPay BNPL payment gateway integration for Laravel 11, 12, and 13',
  head: [
    ['link', { rel: 'icon', href: `${base}logo.svg` }],
    ['meta', { name: 'theme-color', content: '#00D166' }],
  ],

  locales: {
    root: {
      label: 'English',
      lang: 'en-US',
      themeConfig: {
        nav: [
          { text: 'Guide', link: '/guide/getting-started' },
          { text: 'Payment Flow', link: '/guide/payment-token' },
          { text: 'Resilience', link: '/guide/resilience' },
          { text: 'API Reference', link: '/api/facade' },
          { text: 'Official Academy', link: 'https://academy.snapppay.ir/self-cms/' }
        ],
        sidebar: [
          {
            text: 'Introduction',
            items: [
              { text: 'Getting Started', link: '/guide/getting-started' },
              { text: 'Configuration', link: '/guide/configuration' },
            ]
          },
          {
            text: 'Payment Flow',
            items: [
              { text: '1. Eligibility Check', link: '/guide/eligibility' },
              { text: '2. Payment Token & Cart', link: '/guide/payment-token' },
              { text: '3. Callback & Settlement', link: '/guide/callback-and-settlement' },
              { text: 'Resilience (Sections 11 & 12)', link: '/guide/resilience' },
              { text: 'Post-Settlement (Cancel/Update)', link: '/guide/post-settlement' },
            ]
          },
          {
            text: 'Advanced Features',
            items: [
              { text: 'Interface Contracts', link: '/guide/contracts' },
              { text: 'Multi-Tenant & Connections', link: '/guide/multi-tenant' },
              { text: 'Lifecycle Events', link: '/guide/events' },
              { text: 'Testing & Mocking', link: '/guide/testing' },
            ]
          },
          {
            text: 'API Reference',
            items: [
              { text: 'SnappPay Facade', link: '/api/facade' },
              { text: 'Data Transfer Objects (DTOs)', link: '/api/dtos' },
              { text: 'Exceptions', link: '/api/exceptions' },
            ]
          }
        ],
        editLink: {
          pattern: 'https://github.com/eamirgh/snapp-pay-laravel/edit/main/docs/:path',
          text: 'Edit this page on GitHub'
        },
        footer: {
          message: 'Released under the MIT License.',
          copyright: 'Copyright © 2026 Amir Ghaffari'
        }
      }
    },
    fa: {
      label: 'فارسی',
      lang: 'fa-IR',
      dir: 'rtl',
      link: '/fa/',
      themeConfig: {
        nav: [
          { text: 'شروع به کار', link: '/fa/guide/getting-started' },
          { text: 'فرآیند پرداخت', link: '/fa/guide/payment-flow' },
          { text: 'تاب‌آوری (بند ۱۱ و ۱۲)', link: '/fa/guide/resilience' },
          { text: 'مستندات آکادمی', link: 'https://academy.snapppay.ir/self-cms/' }
        ],
        sidebar: [
          {
            text: 'مستندات فارسی',
            items: [
              { text: 'معرفی و نصب', link: '/fa/guide/getting-started' },
              { text: 'اینترفیس‌ها و قراردادها (Contracts)', link: '/fa/guide/contracts' },
              { text: 'بررسی امکان اقساط (Eligible)', link: '/fa/guide/eligibility' },
              { text: 'فرآیند کامل پرداخت و توکن', link: '/fa/guide/payment-flow' },
              { text: 'مدیریت وریفای و تسویه (تاب‌آوری)', link: '/fa/guide/resilience' },
              { text: 'لغو و ویرایش سفارش (پس از تسویه)', link: '/fa/guide/post-settlement' },
            ]
          }
        ],
        outline: {
          label: 'در این صفحه'
        },
        docFooter: {
          prev: 'صفحه قبل',
          next: 'صفحه بعد'
        },
        footer: {
          message: 'منتشر شده تحت مجوز MIT.',
          copyright: 'تمامی حقوق محفوظ است © ۲۰۲۶'
        }
      }
    }
  },

  themeConfig: {
    logo: '/logo.svg',
    socialLinks: [
      { icon: 'github', link: 'https://github.com/eamirgh/snapp-pay-laravel' }
    ],
    search: {
      provider: 'local'
    }
  }
})
