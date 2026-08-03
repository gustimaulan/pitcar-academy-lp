import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';

export default defineConfig({
  site: 'https://academy.pitcar.co.id',
  integrations: [tailwind()],
});
