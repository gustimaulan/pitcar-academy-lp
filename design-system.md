# Pitcar Design System

Dokumen ini menjelaskan spesifikasi desain (Design System) yang digunakan dalam proyek Pitcar Astro. Desain ini dibangun menggunakan Astro, Tailwind CSS, dan CSS Variables untuk fleksibilitas mode terang (Light Mode) dan mode gelap (Dark Mode).

---

## 1. Tipografi (Typography)

Sistem tipografi menggunakan font modern **Inter Variable** yang diintegrasikan melalui `@fontsource-variable/inter`.

### Font Families

- **Sans-serif (Default):** `'Inter Variable'`, `ui-sans-serif`, `system-ui`, ...
- **Serif:** `'Inter Variable'`, `ui-serif`, `Georgia`, ...
- **Heading:** `'Inter Variable'`, `ui-sans-serif`, `system-ui`, ...

### Ukuran & Skala Font

- **Base (pada layar 2xl):** `20px` (`2xl:text-[20px]`) untuk proporsionalitas layout besar.
- **Hero Title (H1):** `text-5xl` (desktop: `text-6xl`), `font-bold`, `leading-tighter`, `tracking-tighter`, `font-heading`.
- **Heading Utama (H2):** `text-3xl` (desktop: `text-4xl`), `font-bold`, `leading-tighter`, `tracking-tighter`, `font-heading`, `text-heading`.
- **Tagline:** `text-base`, `font-bold`, `tracking-wide`, `uppercase`, warna: `text-secondary` (Mode Terang) / `text-blue-200` (Mode Gelap).
- **Subtitle:** `text-xl`, warna: `text-muted` / `text-slate-300` (Mode Gelap).
- **Body Text:** `antialiased`, `tracking-tight`, warna: `text-default`, background: `bg-page`.

---

## 2. Palet Warna (Color Palette)

Skema warna diatur menggunakan CSS variables untuk mendukung dynamic dark mode secara mulus.

| Nama Variabel             | Mode Terang (Light Mode)            | Mode Gelap (Dark Mode)              | Deskripsi / Penggunaan                                          |
| ------------------------- | ----------------------------------- | ----------------------------------- | --------------------------------------------------------------- |
| `--aw-color-primary`      | `rgb(204 0 0)` <br> **#CC0000**     | `rgb(204 0 0)` <br> **#CC0000**     | Warna utama (Red / Pitcar Brand Red).                           |
| `--aw-color-secondary`    | `rgb(170 0 0)` <br> **#AA0000**     | `rgb(170 0 0)` <br> **#AA0000**     | Warna sekunder (Darker Brand Red), digunakan pada hover tombol. |
| `--aw-color-accent`       | `rgb(204 0 0)` <br> **#CC0000**     | `rgb(204 0 0)` <br> **#CC0000**     | Warna aksen.                                                    |
| `--aw-color-text-heading` | `rgb(0 0 0)` <br> **#000000**       | `rgb(247 248 248)` <br> **#F7F8F8** | Warna teks heading utama.                                       |
| `--aw-color-text-default` | `rgb(16 16 16)` <br> **#101010**    | `rgb(229 236 246)` <br> **#E5ECF6** | Warna teks utama body.                                          |
| `--aw-color-text-muted`   | `rgb(16 16 16 / 66%)`               | `rgb(229 236 246 / 66%)`            | Warna teks pelengkap / redup.                                   |
| `--aw-color-bg-page`      | `rgb(255 255 255)` <br> **#FFFFFF** | `rgb(8 8 8)` <br> **#080808**       | Latar belakang halaman utama.                                   |
| `--aw-color-bg-page-dark` | —                                   | `rgb(8 8 8)` <br> **#080808**       | Latar belakang halaman mode gelap khusus.                       |

### Text Selection Colors

- **Mode Terang:** Background `rgb(204 0 0 / 30%)` (Merah transparan) dengan teks `white`.
- **Mode Gelap:** Background `rgb(204 0 0 / 40%)` dengan teks `white`.

---

## 3. Komponen Tombol (Buttons)

Tombol didefinisikan secara standar di dalam file [Button.astro](file:///Users/gdhia/Desktop/GmdhiaRepos/pitcar-astro/src/components/ui/Button.astro) dan [tailwind.css](file:///Users/gdhia/Desktop/GmdhiaRepos/pitcar-astro/src/assets/styles/tailwind.css).

### A. Base / Secondary Button (`.btn` atau `.btn-secondary`)

Tombol standar dengan border tipis abu-abu, teks gelap di mode terang, dan teks terang di mode gelap.

- **Tailwind Classes:**
  ```css
  inline-flex items-center justify-center rounded-full border border-gray-400 bg-transparent font-medium text-center text-base text-page leading-snug transition py-3.5 px-6 md:px-8 ease-in duration-200 focus:ring-blue-500 focus:ring-offset-blue-200 focus:ring-2 focus:ring-offset-2 hover:bg-gray-100 hover:border-gray-600 dark:text-slate-300 dark:border-slate-500 dark:hover:bg-slate-800 dark:hover:border-slate-800 cursor-pointer
  ```

### B. Primary Button (`.btn-primary`)

Tombol utama dengan latar belakang merah Pitcar, teks putih, border merah, dan efek hover transisi menjadi lebih gelap (`--aw-color-secondary`).

- **Tailwind Classes:**
  ```css
  @apply btn font-semibold bg-primary text-white border-primary hover:bg-secondary hover:border-secondary hover:text-white dark:text-white dark:bg-primary dark:border-primary dark:hover:border-secondary dark:hover:bg-secondary;
  ```

### C. Tertiary Button (`.btn-tertiary`)

Tombol minimalis tanpa border dan shadow, menggunakan warna teks redup (`text-muted`).

- **Tailwind Classes:**
  ```css
  @apply btn border-none shadow-none text-muted hover:text-gray-900 dark:text-gray-400 dark:hover:text-white;
  ```

### D. Link Button (`.btn-link`)

Tombol berupa link teks dengan efek hover berwarna primer.

- **Tailwind Classes:**
  ```css
  cursor-pointer hover:text-primary
  ```

---

## 4. Tata Letak & Jarak (Layout & Spacing)

### Grid & Max Width

- **Kontainer Utama Halaman:** `max-w-7xl mx-auto px-4 sm:px-6` (lebar maks `1280px` dengan padding samping responsif).
- **Kontainer Headline:** `max-w-3xl mb-8 md:mx-auto md:mb-12 text-center` (digunakan pada judul seksi halaman).
- **Section Padding:** `py-12 md:py-16 lg:py-20` (padding vertikal responsif pada [WidgetWrapper.astro](file:///Users/gdhia/Desktop/GmdhiaRepos/pitcar-astro/src/components/ui/WidgetWrapper.astro)).
- **Scroll Margin Top:** `scroll-mt-[72px]` (mencegah teks tertutup header saat navigasi scroll internal).

### Header & Sticky Navigation

- **Header Light Mode (saat di-scroll):**
  - Background: `@apply bg-page md:bg-white/90 md:backdrop-blur-md;`
  - Shadow: `0 0.375rem 1.5rem 0 rgb(140 152 164 / 13%)`
- **Header Dark Mode (saat di-scroll):**
  - Background: `@apply bg-page md:bg-[#030621e6] border-b border-gray-500/20;`
  - Shadow: `none`

---

## 5. Animasi & Interaksi

### Fade In Up

Digunakan secara luas di berbagai widget (seperti Hero, Features, dll.) dikombinasikan dengan Intersection Observer (Tailwind plugin `intersect`):

- **Animasi:** `fade` (`fadeInUp 1s both`)
- **Keyframes:**
  ```css
  @keyframes fadeInUp {
    0% {
      opacity: 0;
      transform: translateY(2rem);
    }
    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }
  ```
- **Penggunaan Class:**
  ```html
  class="intersect-once intersect-quarter motion-safe:md:opacity-0
  motion-safe:md:intersect:animate-fade"
  ```
