# Product Requirements Document: Pitcar Academy Landing Page

**Version**: 1.0  
**Date**: June 2025  
**Status**: Draft — Review  

---

## 1. Executive Summary

### Problem Statement
Pitcar Academy belum memiliki platform digital yang terstandarisasi untuk menarik calon peserta didik dan mengonversi mereka menjadi pendaftar program pelatihan mekanik. Informasi tersedia namun tidak tersaji dalam format yang profesional, mudah dipahami, dan mengoptimalkan konversi melalui WhatsApp.

### Proposed Solution
Membangun landing page single-page yang SEO-friendly, cepat, dan conversion-oriented dengan fokus utama mengarahkan pengunjung untuk langsung chat via WhatsApp. Landing page dibuat dengan struktur arsitektur yang memungkinkan penambahan modul LMS (`/admin`) di masa depan.

### Success Criteria (KPIs)
| Metric | Target |
|--------|--------|
| WhatsApp Click Rate (CTR) | ≥ 8% dari total kunjungan |
| Lighthouse Performance Score | ≥ 95 (desktop & mobile) |
| Lighthouse Accessibility Score | ≥ 95 |
| Core Web Vitals | All "Good" (LCP < 2.5s, CLS < 0.1, INP < 200ms) |
| Bounce Rate | ≤ 45% |

---

## 2. User Experience & Functionality

### User Personas

| Persona | Deskripsi | Motivation | Pain Point |
|---------|-----------|------------|------------|
| **Pemula (Age 17-22)** | Lulusan SMA/SMK yang ingin belajar keahlian praktis | Ingin segera kerja, biaya terjangkau | Tidak tahu harus mulai dari mana |
| **Career Switcher (Age 23-35)** | Pekerja umum yang ingin beralih ke bidang otomotif | Cari karier stabil dengan prospek bagus | Ragu apakah bisa belajar dari nol |
| **Parent (Age 40-50)** | Orang tua yang mencari pendidikan teknis untuk anak | Keamanan karier anak jangka panjang | Butuh info jelas soal sertifikat & peluang kerja |

### User Stories & Acceptance Criteria

#### STORY 1: Pengunjung Menemukan LP via Google
> As a potential student searching online, I want to find Pitcar Academy through Google search results so I can learn about the training programs.

**Acceptance Criteria:**
- Meta title mengandung keyword relevan (contoh: "Sekolah Mekanik Profesional Jakarta")
- Meta description ada, max 155 karakter, mengandung CTA WhatsApp
- Open Graph tags terisi (preview link media sosial)
- Semantic HTML (H1-H6 hierarchy benar, article, section, nav, main)
- JSON-LD structured data (LocalBusiness / EducationalOrganization + Course schema)
- Robots.txt dan sitemap.xml generate otomatis

#### STORY 2: Pengunjung Memahami Penawaran Utama
> As a visitor who landed on the LP, I want to immediately understand what Pitcar Academy offers and why I should choose it, so I don't leave confused.

**Acceptance Criteria:**
- Hero section muncul tanpa scroll: headline value proposition + subheadline + 1 CTA button WhatsApp
- Badge/label menampilkan key selling point (sertifikat resmi, praktik di bengkel)
- Load time hero section < 1s
- Text contrast ratio minimal AA compliant (4.5:1)

#### STORY 3: Pengunjung Melihat Paket Belajar
> As a potential student, I want to clearly see all available learning packages with their prices, materials, and outcomes, so I can decide which one fits me.

**Acceptance Criteria:**
- 3 kartu paket ditampilkan: Basic, Advanced, Professional
- Setiap kartu menampilkan: nama paket, level, harga (Rp), durasi, OJT info, sertifikat, daftar materi (collapsible/detail view opsional)
- Paket Professional diberi highlight visual sebagai "Best Value" atau "Recommended"
- CTA WhatsApp per paket dengan pesan pre-filled berbeda
- Responsive: card stack pada mobile, grid pada desktop

#### STORY 4: Pengunjung Yakin Memilih Pitcar Academy
> As a hesitant visitor, I want to see why Pitcar Academy is better than competitors, so I feel confident making a decision.

**Acceptance Criteria:**
- Section "Keunggulan Pitcar Academy" menampilkan 5 keunggulan dengan icon
- Tiap keunggulan punya judul singkat + deskripsi 1 kalimat
- Tampilan visual bersih: 2 kolom di mobile, 5 kolom di desktop (atau horizontal scroll swipe)

#### STORY 5: Pengunjung Langsung Chat via WhatsApp
> As an interested visitor, I want to easily start a conversation on WhatsApp without filling any form or registering first.

**Acceptance Criteria:**
- CTA WhatsApp sticky/floating button selalu terlihat (bottom-right, fixed position)
- Floating button animasi subtle (pulse/bounce) untuk menarik perhatian
- Minimal 3 posisi CTA WhatsApp: Hero, Akhir konten (section terakhir), Floating bottom-right
- URL WhatsApp pakai `wa.me` + parameter `text=` dengan pesan pre-filled sesuai section
- Nomor WhatsApp dikonfigurasi via env/config (mudah diganti tanpa edit kode)
- Klik CTA membuka tab baru (target blank)

#### STORY 6: Pengunjung Mengakses LP dari HP
> As a mobile user, I want the LP to be perfectly responsive and fast on my phone, so I can browse comfortably anywhere.

**Acceptance Criteria:**
- Fully responsive layout tested pada 375px, 414px, 768px, 1024px viewport
- Touch target minimal 44x44px (WCAG 2.5.5)
- Font size minimum 16px pada input/konponen interaktif (mencegah zoom iOS)
- Tidak ada horizontal scroll pada mobile
- Hamburger menu jika navigasi section (opsional)

#### STORY 7: Admin Update Konten Tanpa Deploy Ulang
> As a marketing admin, I want to update package info, prices, or test content through a simple config file, so the team can make quick updates.

**Acceptance Criteria:**
- Semua konten dinamis (paket, harga, testimoni, nomor WA) didefinisikan di satu file `content.config.ts`
- Edit konten = edit 1 file → rebuild/deploy ulang
- Struktur config terpisah: `packages`, `advantages`, `contactInfo`, `seoConfig`

---

### Non-Goals
- **Form pendaftaran / lead capture** (tidak ada form di LP)
- **Payment gateway integration** (eksklusi pembayaran online)
- **User authentication** (tidak diperlukan untuk LP)
- **Blog / news section** (fokus landing page saja)
- **Multi-language** (Indonesia only untuk MVP)
- **Admin panel / CMS** (eksklusi — LMS di phase terpisah)
- **Real-time chat widget** (hanya WhatsApp redirect)

---

## 3. AI System Requirements

N/A — Landing page ini bersifat statis/informasional tanpa komponen AI.

---

## 4. Technical Specifications

### Architecture Overview

```
┌──────────────────────────────────────────────┐
│              PITCAR ACADEMY LP               │
│           (Astro Static Site)                │
├──────────────────────────────────────────────┤
│                                              │
│  /            → Main Landing Page           │
│                 (Hero, Packages, CTA WA)    │
│                                              │
│  /faq          → FAQ page (optional)        │
│  /testimoni    → Testimoni page (optional)  │
│                                              │
│  /admin        → RESERVED ROUTE             │
│                 → Redirect placeholder        │
│                 → Siap jadi LMS (React/Vue) │
│                 → Future: actual LMS app     │
│                                              │
├──────────────────────────────────────────────┤
│  Content Layer:                              │
│  - content.config.ts  (data source)         │
│  - assets/images/   (optimized images)      │
│                                              │
└──────────────────────────────────────────────┘
```

### Tech Stack

| Layer | Technology | Justification |
|-------|-----------|---------------|
| **Framework** | Astro 5.x | Static-first, zero JS by default, perfect for SEO LP |
| **Styling** | Tailwind CSS 4.x | Utility-first, small bundle, easy customization |
| **Icons** | Heroicons / Lucide | Clean SVG icons, tree-shakeable |
| **Font** | Geist Sans / Inter | Modern, readable, free license |
| **Deployment** | Docker / PM2 on VPS | Self-hosted, full control, cost-effective |
| **Build** | GitHub Actions (CI/CD) | Auto build + deploy on push |

### Directory Structure

```
pitcar-academy-lp/
├── public/
│   ├── favicon.svg
│   ├── robots.txt
│   └── sitemap.xml          # Generated at build
├── src/
│   ├── content/
│   │   └── config.ts        ← Data configuration (packages, etc.)
│   ├── layouts/
│   │   └── BaseLayout.astro ← Global layout, navbar, footer
│   ├── components/
│   │   ├── Header.astro     ← Navbar section
│   │   ├── Footer.astro     ← Footer dengan kontak info
│   │   ├── WhatsAppButton.astro ← Floating sticky CTA
│   │   ├── HeroSection.astro        ← Hero with value prop + CTA
│   │   ├── AboutSection.astro       ← "Tentang Kami" mini
│   │   ├── PackageCard.astro        ← Reusable package card
│   │   ├── PackageGrid.astro        ← Package display grid
│   │   ├── AdvantageSection.astro   ← Keunggulan cards
│   │   ├── FaqSection.astro         ← Accordion FAQ
│   │   ├── CtaSection.astro         ← Bottom CTA banner
│   │   └── SeoHead.astro            ← Reusable meta tags
│   ├── pages/
│   │   ├── index.astro        ← Main landing page
│   │   ├── faq.astro          ← Detailed FAQ page
│   │   └── admin/
│   │       └── index.astro    ← Placeholder (/admin route reserved)
│   └── styles/
│       └── global.css         ← Global styles + Tailwind imports
├── astro.config.mjs
├── tailwind.config.mjs
├── package.json
├── tsconfig.json
├── Dockerfile
└── nginx.conf                 ← VPS reverse proxy config
```

### Integration Points

| Integration | Purpose | Implementation |
|-------------|---------|----------------|
| **WhatsApp API** | Direct chat via `wa.me` | Static URL dengan encoded message |
| **Google Search Console** | SEO monitoring | Submit sitemap.xml |
| **Vercel Analytics / Plausible** *(Optional)* | Traffic tracking | Lightweight privacy-first script |

### WhatsApp Link Configuration

All WhatsApp links follow this pattern:

```
https://wa.me/{nomor}?text={pesan_terenkodi}
```

Example messages:
- Hero CTA: `"Halo Pitcar Academy, saya tertarik dengan program pelatihan mekanik. Bisa info lebih lanjut?"`
- Package Specific: `"Halo, saya tertarik dengan PAKET BASIC - Rp 5.000.000. Bagaimana cara daftarnya?"`
- Sticky Button: `"Halo Pitcar Academy, saya mau daftar program pelatihan mekanik."`

Number and messages are defined centrally in `content.config.ts`.

### SEO Implementation Checklist

- [ ] Unique `<title>` per page (max 60 karakter optimal)
- [ ] Unique `<meta description>` per page (max 155 karakter)
- [ ] Canonical URL per page
- [ ] Open Graph (og:title, og:description, og:image, og:type=website)
- [ ] Twitter Card (summary_large_image)
- [ ] JSON-LD structured data:
  - `EducationalOrganization` schema
  - `Course` schema per paket
  - `FAQPage` schema untuk halaman FAQ
- [ ] Semantic HTML5 elements: `<header>`, `<main>`, `<section>`, `<article>`, `<footer>`
- [ ] Correct heading hierarchy (H1 → H2 → H3)
- [ ] Image alt text wajib
- [ ] Internal linking antar halaman
- [ ] Sitemap.xml auto-generate
- [ ] Fast loading (critical CSS inline, lazy-load non-critical)

### Security & Privacy

| Aspect | Approach |
|--------|----------|
| HTTPS |强制 via VPS Nginx config + Let's Encrypt |
| CSP | Basic CSP header (self + wa.me) |
| Form security | N/A (no forms) |
| WhatsApp safety | No sensitive data transmitted; external link warning if needed |

---

## 5. Risks & Roadmap

### Phased Rollout

| Phase | Scope | Timeline Estimate |
|-------|-------|-------------------|
| **Phase 1 — MVP** | LP + WhatsApp CTA + SEO basic | Week 1-2 |
| **Phase 2 — Enhanced** | FAQ page + Testimoni + Rich snippets + Analytics | Week 3 |
| **Phase 3 — Performance** | Image optimization + Cache strategy + CDN setup | Week 4 |
| **Phase 4 — LMS Ready** | `/admin` route converted to React/Vue SPA with auth | Month 2+ |

### Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| WhatsApp link open rate rendah | Konversi rendah | A/B testing CTA copy + floating button always visible |
| LP lambat di network buruk | Bounce tinggi tinggi | Static generation, compressed assets, aggressive caching |
| Perubahan konten butuh rebuild | Agility issue | Centralized config file untuk semua konten bisnis |
| `/admin` conflict dengan Astro routing | Build error | Gunakan folder-based nesting `pages/admin/` dengan proper exclusion |
| VPS uptime down | LP offline | Health check + alerting (UptimeRobot/UptimeKuma) |

### Design Guidelines Reference

Design akan mengikuti brand identity yang ada di **pitcar.co.id**:
- Warna utama mengikuti palette Pitcar (hitam, merah, putih)
- Typography modern dan professional
- Visual tone: industri otomotif yang clean dan trustworthy
- Brand asset (logo, icon) dari pitcar.co.id

---

## Appendix: WhatsApp Pre-filled Messages Detail

```typescript
// content.config.ts example
export const whatsappConfig = {
  phoneNumber: "628XXXXXXXXX",  // Ganti dengan nomor official
  baseUrl: "https://wa.me",
  messages: {
    hero: encodeURIComponent(
      "Halo Pitcar Academy, saya tertarik dengan program pelatihan mekanik. Bisa info lebih lanjut?"
    ),
    packageBasic: encodeURIComponent(
      "Halo, saya tertarik dengan PAKET BASIC Maintenance Mobil EFI - Rp 5.000.000. Bagaimana cara daftarnya?"
    ),
    packageAdvanced: encodeURIComponent(
      "Halo, saya tertarik dengan PAKET ADVANCED General Repair Mobil EFI - Rp 5.000.000. Bagaimana cara daftarnya?"
    ),
    packageProfessional: encodeURIComponent(
      "Halo, saya tertarik dengan PAKET PROFESSIONAL Level 1&2 - Rp 8.500.000. Bagaimana cara daftarnya?"
    ),
    sticky: encodeURIComponent(
      "Halo Pitcar Academy, saya mau tanya tentang program pelatihan mekanik."
    ),
  },
};
```

---

*End of PRD v1.0*
