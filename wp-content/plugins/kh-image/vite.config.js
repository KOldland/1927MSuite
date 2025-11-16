import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
	root: path.resolve(__dirname, 'admin/src'),
	base: './',
	plugins: [react()],
	build: {
		outDir: path.resolve(__dirname, 'admin/dist'),
		emptyOutDir: true,
		manifest: true,
		rollupOptions: {
			input: path.resolve(__dirname, 'admin/src/main.jsx'),
		},
	},
	server: {
		port: 5176,
	},
});
