export const collections = {
  // Content data — semua info bisnis di sini
};

// WhatsApp configuration (not a collection, just exported config)
export const whatsappConfig = {
  phoneNumber: '6281234567890', // Ganti dengan nomor official WA
  messages: {
    hero: encodeURIComponent(
      'Halo Pitcar Academy, saya tertarik dengan program pelatihan mekanik. Bisa info lebih lanjut?'
    ),
    packageBasic: encodeURIComponent(
      'Halo, saya tertarik dengan PAKET BASIC Maintenance Mobil EFI - Rp 5.000.000. Bagaimana cara daftarnya?'
    ),
    packageAdvanced: encodeURIComponent(
      'Halo, saya tertarik dengan PAKET ADVANCED General Repair Mobil EFI - Rp 5.000.000. Bagaimana cara daftarnya?'
    ),
    packageProfessional: encodeURIComponent(
      'Halo, saya tertarik dengan PAKET PROFESSIONAL Level 1&2 Maintenance & General Repair - Rp 8.500.000. Bagaimana cara daftarnya?'
    ),
    sticky: encodeURIComponent(
      'Halo Pitcar Academy, saya mau tanya tentang program pelatihan mekanik.'
    ),
    faq: encodeURIComponent(
      'Halo Pitcar Academy, saya punya pertanyaan setelah membaca FAQ. Bisa dibantu?'
    ),
    ctaBottom: encodeURIComponent(
      'Halo Pitcar Academy, saya ingin mendaftar program pelatihan mekanik!'
    ),
  },
};

// Package data
export interface PackageItem {
  id: string;
  level: string;
  name: string;
  description: string;
  price: string;
  priceDisplay: string;
  duration: string;
  hours: string;
  ojt: string;
  certificate: string;
  kickOff: string;
  materials: string[];
  outcomes: string[];
  highlight?: boolean;
  image?: string;
}

export const packages: PackageItem[] = [
  {
    id: 'basic',
    level: 'LEVEL 1',
    name: 'MAINTENANCE MOBIL BENSIN EFI',
    description:
      'Program dasar untuk menjadi mekanik handal di bidang perawatan berkala dan sistem EFI. Cocok untuk pemula yang ingin memulai karier di dunia otomotif.',
    price: '5000000',
    priceDisplay: 'Rp 5.000.000,-',
    duration: '1 Bulan',
    hours: '104 Jam',
    ojt: '2 Bulan',
    certificate: 'PITCAR CERTIFIED MECHANIC LEVEL 1',
    kickOff: 'OKTOBER 2026',
    image: '/pkg-basic.png',
    materials: [
      'Tune Up & Perawatan Berkala',
      'Sistem Bahan Bakar EFI',
      'Sistem Pengapian',
      'Sistem Pendingin Mesin',
      'Sistem Pelumasan',
      'Sistem Rem Dasar',
      'Scanner EFI Dasar',
      'SOP & Pelayanan Customer',
    ],
    outcomes: [
      'Melakukan perawatan berkala',
      'Melakukan tune up mesin bensin EFI',
      'Mengganti komponen rutin',
      'Menggunakan scanner dasar',
      'Membaca keluhan dasar',
      'Menerapkan SOP kerja bengkel',
      'Melayani customer dengan profesional',
    ],
  },
  {
    id: 'advanced',
    level: 'LEVEL 2',
    name: 'GENERAL REPAIR MOBIL EFI',
    description:
      'Tingkat lanjutan untuk mekanik yang ingin menguasai perbaikan komponen dan sistem kendaraan secara lebih mendalam.',
    price: '5000000',
    priceDisplay: 'Rp 5.000.000,-',
    duration: '1 Bulan',
    hours: '104 Jam',
    ojt: '2 Bulan',
    certificate: 'PITCAR CERTIFIED MECHANIC LEVEL 2',
    kickOff: 'NOVEMBER 2026',
    image: '/pkg-advanced.png',
    materials: [
      'Sistem Engine & Komponen',
      'Overhaul Engine',
      'Sistem Transmisi',
      'Sistem Rem Lanjutan',
      'Sistem Suspensi & Steering',
      'Sistem Kelistrikan Dasar',
      'Analisa Kerusakan',
      'Penggunaan Alat Khusus',
      'Troubleshooting Komponen Kendaraan',
    ],
    outcomes: [
      'Melakukan overhaul & perbaikan mesin',
      'Memperbaiki sistem transmisi',
      'Melakukan diagnosa kerusakan',
      'Menggunakan alat special tools',
      'Memperbaiki rem, suspensi & steering',
      'Melakukan troubleshooting komponen',
    ],
  },
  {
    id: 'professional',
    level: 'LEVEL 1 & 2',
    name: 'MAINTENANCE & GENERAL REPAIR',
    description:
      'Program lengkap untuk menjadi mekanik profesional yang menguasai perawatan dan perbaikan kendaraan secara menyeluruh. Siap kerja, siap bersaing!',
    price: '8500000',
    priceDisplay: 'Rp 8.500.000,-',
    duration: '2 Bulan',
    hours: '208 Jam',
    ojt: '2 Bulan',
    certificate: 'PITCAR PROFESSIONAL TECHNICIAN',
    kickOff: 'OKTOBER 2026',
    image: '/pkg-professional.png',
    materials: [
      'Semua materi Level 1 (Maintenance)',
      'Semua materi Level 2 (General Repair)',
      'Diagnosa Lanjutan',
      'Efisiensi & Quality Control',
      'Teknik Problem Solving',
      'Komunikasi & Pelayanan Customer',
      'Bengkel Profesional',
    ],
    outcomes: [
      'Menguasai perawatan dan perbaikan menyeluruh',
      'Melakukan diagnosa & troubleshooting tingkat lanjut',
      'Menggunakan alat teknologi bengkel modern',
      'Bekerja mandiri dan profesional',
      'Siap kerja di jaringan Pitcar atau bengkel mitra',
    ],
    highlight: true,
  },
];

// Advantage / Keunggulan data
export interface AdvantageItem {
  icon: string;
  title: string;
  description: string;
}

export const advantages: AdvantageItem[] = [
  {
    icon: 'cog',
    title: 'Trainer Profesional',
    description: 'Dibimbing langsung oleh trainer berpengalaman di industri otomotif.',
  },
  {
    icon: 'wrench',
    title: 'Praktik Langsung',
    description: 'OJT di bengkel Pitcar berstandar tinggi dengan peralatan modern.',
  },
  {
    icon: 'shield',
    title: 'Sertifikasi Resmi',
    description: 'Sertifikat bertahap sesuai kompetensi yang dicapai.',
  },
  {
    icon: 'users',
    title: 'Pelatihan Karakter',
    description: 'Disiplin, jujur, dan berorientasi layanan pelanggan.',
  },
  {
    icon: 'briefcase',
    title: 'Peluang Karier',
    description: 'Terhubung langsung dengan jaringan Pitcar Service dan mitra.',
  },
];

// FAQ data
export interface FaqItem {
  question: string;
  answer: string;
}

export const faqs: FaqItem[] = [
  {
    question: 'Apakah boleh ikut tanpa background otomotif?',
    answer:
      'Boleh! Program Basic dirancang khusus untuk pemula yang belum pernah belajar otomotif. Anda akan belajar dari nol hingga mampu mengerjakan perawatan dasar mobil.',
  },
  {
    question: 'Berapa lama total waktu pelatihan?',
    answer:
      'Basic 1 bulan kelas + 2 bulan OJT. Advanced 1 bulan kelas + 2 bulan OJT. Professional 2 bulan kelas + 2 bulan OJT. Total ada 3 bulan termasuk praktik di bengkel.',
  },
  {
    question: 'Bagaimana sistem pembelajaran?',
    answer:
      'Kombinasi teori dan praktik. Sesi belajar di dalam kelas dilanjutkan dengan Praktik Kerja Lapangan (OJT) langsung di bengkel Pitcar Service selama 2 bulan.',
  },
  {
    question: 'Apa yang didapat setelah lulus?',
    answer:
      'Anda mendapat sertifikat resmi dari Pitcar Academy, kemampuan teknis sesuai level, pengalaman praktik di bengkel, dan kesempatan bergabung di jaringan Pitcar.',
  },
  {
    question: 'Apakah bisa cicilan?',
    answer:
      'Untuk info program pembayaran cicilan, silakan hubungi kami melalui WhatsApp. Kami memiliki beberapa pilihan payment plan yang bisa disesuaikan.',
  },
  {
    question: 'Dimana lokasi bengkel untuk OJT?',
    answer:
      'OJT dilaksanakan di bengkel-bengkel Pitcar Service yang tersebar di berbagai lokasi strategis. Detail lokasi akan disampaikan saat konfirmasi pendaftaran.',
  },
];
